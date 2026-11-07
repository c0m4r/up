<?php

declare(strict_types=1);

namespace Up;

use UnexpectedValueException;

/**
 * Re-encodes animated GIFs with GD.
 *
 * GD only reads the first frame of a GIF, so every frame is cut out into a
 * standalone image, decoded and encoded again by GD, and then stitched back
 * into an animation with the original timing and disposal. Only pixel data
 * that survived a full decode ends up in the output: comments, application
 * extensions (except the loop count) and bytes after the trailer are dropped.
 *
 * @phpstan-type Frame array{
 *     left: int,
 *     top: int,
 *     width: int,
 *     height: int,
 *     interlaced: bool,
 *     colorTable: string,
 *     imageData: string,
 *     delay: int,
 *     disposal: int,
 *     transparentIndex: int|null,
 * }
 */
final class AnimatedGif
{
    /** Upper bound on frames, keeps parsing and re-encoding time and memory in check */
    public const int MAX_FRAMES = 1000;

    /**
     * @param list<Frame> $frames
     */
    private function __construct(
        private readonly int $width,
        private readonly int $height,
        private readonly ?int $loops,
        private readonly array $frames,
    ) {
    }

    /**
     * @throws UnexpectedValueException if the data is not a well-formed GIF
     */
    public static function parse(string $data): self
    {
        $offset = 0;
        $signature = self::read($data, $offset, 6);

        if ($signature !== 'GIF87a' && $signature !== 'GIF89a') {
            throw new UnexpectedValueException('Not a GIF image');
        }

        // Logical screen descriptor
        $width = self::uint16($data, $offset);
        $height = self::uint16($data, $offset);
        $flags = self::uint8($data, $offset);
        self::read($data, $offset, 2); // background color index, pixel aspect ratio
        $globalColorTable = self::readColorTable($data, $offset, $flags);

        $loops = null;
        $control = null;
        $frames = [];

        // A missing trailer is tolerated, browsers render such files as well
        while ($offset < strlen($data)) {
            $introducer = self::uint8($data, $offset);

            if ($introducer === 0x3B) {
                break;
            }

            if ($introducer === 0x21) {
                $label = self::uint8($data, $offset);
                $blocks = self::readSubBlocks($data, $offset);

                if ($label === 0xF9 && strlen($blocks[0] ?? '') === 4) {
                    $packed = ord($blocks[0][0]);
                    $control = [
                        'delay' => ord($blocks[0][1]) | (ord($blocks[0][2]) << 8),
                        'disposal' => ($packed >> 2) & 0x07,
                        'transparentIndex' => ($packed & 0x01) === 0x01 ? ord($blocks[0][3]) : null,
                    ];
                } elseif (
                    $label === 0xFF
                    && ($blocks[0] ?? '') === 'NETSCAPE2.0'
                    && strlen($blocks[1] ?? '') === 3
                    && ord($blocks[1][0]) === 0x01
                ) {
                    $loops = ord($blocks[1][1]) | (ord($blocks[1][2]) << 8);
                }

                continue;
            }

            if ($introducer !== 0x2C) {
                throw new UnexpectedValueException('Unknown GIF block');
            }

            if (count($frames) === self::MAX_FRAMES) {
                throw new UnexpectedValueException('Too many GIF frames');
            }

            // Image descriptor
            $left = self::uint16($data, $offset);
            $top = self::uint16($data, $offset);
            $frameWidth = self::uint16($data, $offset);
            $frameHeight = self::uint16($data, $offset);
            $packed = self::uint8($data, $offset);
            $colorTable = self::readColorTable($data, $offset, $packed) ?? $globalColorTable;

            if ($colorTable === null) {
                throw new UnexpectedValueException('GIF frame without a color table');
            }

            // LZW minimum code size followed by the compressed sub-blocks
            $start = $offset;
            self::read($data, $offset, 1);
            self::readSubBlocks($data, $offset);

            $frames[] = [
                'left' => $left,
                'top' => $top,
                'width' => $frameWidth,
                'height' => $frameHeight,
                'interlaced' => ($packed & 0x40) === 0x40,
                'colorTable' => $colorTable,
                'imageData' => substr($data, $start, $offset - $start),
                'delay' => $control['delay'] ?? 0,
                'disposal' => $control['disposal'] ?? 0,
                'transparentIndex' => $control['transparentIndex'] ?? null,
            ];
            $control = null;
        }

        if ($frames === []) {
            throw new UnexpectedValueException('GIF without image data');
        }

        return new self($width, $height, $loops, $frames);
    }

    public function isAnimated(): bool
    {
        return count($this->frames) > 1;
    }

    /**
     * @throws UnexpectedValueException if GD fails to decode or encode a frame
     */
    public function reencode(): string
    {
        $gif = 'GIF89a' . self::pack16($this->width) . self::pack16($this->height) . "\x00\x00\x00";

        if ($this->loops !== null) {
            $gif .= "\x21\xFF\x0BNETSCAPE2.0\x03\x01" . self::pack16($this->loops) . "\x00";
        }

        foreach ($this->frames as $frame) {
            $encoded = self::reencodeFrame($frame);
            $encoded['left'] = $frame['left'];
            $encoded['top'] = $frame['top'];
            $encoded['delay'] = $frame['delay'];
            $encoded['disposal'] = $frame['disposal'];
            $gif .= self::encodeFrame($encoded);
        }

        return $gif . "\x3B";
    }

    /**
     * Runs a single frame through GD and returns it as parsed from GD's output.
     *
     * @param Frame $frame
     * @return Frame
     */
    private static function reencodeFrame(array $frame): array
    {
        $frame['left'] = 0;
        $frame['top'] = 0;
        $standalone = 'GIF89a' . self::pack16($frame['width']) . self::pack16($frame['height']) . "\x00\x00\x00"
            . self::encodeFrame($frame) . "\x3B";

        $image = imagecreatefromstring($standalone);

        if ($image === false) {
            throw new UnexpectedValueException('Unable to decode a GIF frame');
        }

        ob_start();
        $written = imagegif($image);
        $output = ob_get_clean();

        if (!$written || !is_string($output)) {
            throw new UnexpectedValueException('Unable to encode a GIF frame');
        }

        $encoded = self::parse($output)->frames;

        if (count($encoded) !== 1 || $encoded[0]['width'] !== $frame['width'] || $encoded[0]['height'] !== $frame['height']) {
            throw new UnexpectedValueException('Unexpected GD output');
        }

        return $encoded[0];
    }

    /**
     * Graphic control extension, image descriptor, local color table and image data.
     *
     * @param Frame $frame
     */
    private static function encodeFrame(array $frame): string
    {
        $transparent = $frame['transparentIndex'] !== null ? 0x01 : 0x00;
        $interlaced = $frame['interlaced'] ? 0x40 : 0x00;
        $tableSize = 0;

        while ((2 << $tableSize) < intdiv(strlen($frame['colorTable']), 3)) {
            $tableSize++;
        }

        return "\x21\xF9\x04" . self::byte(($frame['disposal'] << 2) | $transparent) . self::pack16($frame['delay'])
            . self::byte($frame['transparentIndex'] ?? 0) . "\x00"
            . "\x2C" . self::pack16($frame['left']) . self::pack16($frame['top'])
            . self::pack16($frame['width']) . self::pack16($frame['height'])
            . self::byte(0x80 | $interlaced | $tableSize)
            . $frame['colorTable'] . $frame['imageData'];
    }

    /**
     * Reads the color table announced by the packed field of a descriptor, if any.
     */
    private static function readColorTable(string $data, int &$offset, int $packed): ?string
    {
        if (($packed & 0x80) === 0) {
            return null;
        }

        return self::read($data, $offset, 3 << (($packed & 0x07) + 1));
    }

    /**
     * @return list<string> payloads of the data sub-blocks up to the block terminator
     */
    private static function readSubBlocks(string $data, int &$offset): array
    {
        $blocks = [];

        while (($size = self::uint8($data, $offset)) !== 0) {
            $blocks[] = self::read($data, $offset, $size);
        }

        return $blocks;
    }

    private static function uint8(string $data, int &$offset): int
    {
        return ord(self::read($data, $offset, 1));
    }

    private static function uint16(string $data, int &$offset): int
    {
        $bytes = self::read($data, $offset, 2);

        return ord($bytes[0]) | (ord($bytes[1]) << 8);
    }

    private static function byte(int $value): string
    {
        return chr($value & 0xFF);
    }

    private static function pack16(int $value): string
    {
        return self::byte($value) . self::byte($value >> 8);
    }

    private static function read(string $data, int &$offset, int $length): string
    {
        if ($offset + $length > strlen($data)) {
            throw new UnexpectedValueException('Truncated GIF data');
        }

        $bytes = substr($data, $offset, $length);
        $offset += $length;

        return $bytes;
    }
}
