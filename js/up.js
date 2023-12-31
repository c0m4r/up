function fileSelected()
{
	var file = document.getElementById('up').files[0];
	
	if(file)
	{
		var fileSize = 0;
        var max_file_size = $("input[name=MAX_FILE_SIZE]").val();
		
		if (file.size > 1024 * 1024)
		{
			fileSize = (Math.round(file.size * 100 / (1024 * 1024)) / 100).toString() + 'MB';
		}
		else
		{
			fileSize = (Math.round(file.size * 100 / 1024) / 100).toString() + 'KB';
		}

		if(file.size <= max_file_size)
		{
			document.getElementById('fileInfo').innerHTML = escapeHtml(file.name) + ' (<span id="filesize">' + escapeHtml(fileSize) + '</span>)';
			uploadFile();
		}
		else if(file.size > max_file_size)
		{
			$(".info").text("file too large");
			$("#progressNumber").text("");
			$("progress").val("0");
			$(".results").hide();
			return false;
		}
	}
}

function uploadFile()
{
	var fd = new FormData();
	fd.append("up", document.getElementById('up').files[0]);
	var xhr = new XMLHttpRequest();
	xhr.upload.addEventListener("progress", uploadProgress, false);
	xhr.addEventListener("load", uploadComplete, false);
	xhr.addEventListener("error", uploadFailed, false);
	xhr.addEventListener("abort", uploadCanceled, false);
	xhr.open("POST", "upload.php");
	xhr.send(fd);
}

function uploadProgress(evt)
{
	if (evt.lengthComputable)
	{
		var percentComplete = Math.round(evt.loaded * 100 / evt.total);
		document.getElementById('progressNumber').innerHTML = percentComplete.toString() + '%';
		$("progress").val(percentComplete.toString());

		if(percentComplete == 100)
		{
			$(".malscan").show();
		}
	}
	else
	{
		document.getElementById('progressNumber').innerHTML = 'progress unavailable';
	}
}

function uploadComplete(evt)
{
	var callback = evt.target.responseText;
	
	if(callback[0] == "{")
	{
		var callback = jQuery.parseJSON(callback);
		
		if(callback.error)
		{
			$(".info").text(callback.error);
			$("#progressNumber").text("");
			$("progress").val("0");
			$(".results").hide();
			$(".malscan").hide();
		}
		else if(callback.msg)
		{
			if($(".image").html())
			{
				$(".image a").prependTo(".last");
			}
			
			$(".results").slideUp().slideToggle();
			$(".result").val(callback.msg).show();

			if(window.innerWidth >= 600)
			{
			    $("input[type=text]").select();
			    $(".result").select();
			}

			$(".image a").remove();
			$(".image").append('<a href="' + callback.msg + '" target="_blank"><img src="' + callback.msg + '" alt="" /></a>');
			$("progress").val("100");
			$("#progressNumber").text("100%");
			$(".malscan").hide();
		}
	}
	else if(callback[0] != "{")
	{
		$(".info").text("error");
		$("#progressNumber").text("");
		$("progress").val("0");
		$(".results").hide();
		$(".malscan").hide();
	}
}

function uploadFailed()
{
	alert("upload error");
}

function uploadCanceled()
{
	alert("upload canceled");
}

function escapeHtml(text) {
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};

	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

if(window.innerWidth >= 600)
{
    $(".result").focus(function()
    {
	    $(".ctrl").html("<kbd class=\"light\">CTRL</kbd> + <kbd class=\"light\">C</kbd> = URL");
    });

    $(".result_html").focus(function()
    {
	    $(".ctrl").html("<kbd class=\"light\">CTRL</kbd> + <kbd class=\"light\">C</kbd> = HTML");
    });

    $(".result_forum").focus(function()
    {
	    $(".ctrl").html("<kbd class=\"light\">CTRL</kbd> + <kbd class=\"light\">C</kbd> = FORUM");
    });

    $("input[type=text]").click(function() {
        this.select();
    });
}

$(".flyhigh").change(function() {
    return fileSelected();
});

$("#ts-a").click(function() { $('.rules p').hide(); $('#ts').slideToggle(); return false; });
$("#pp-a").click(function() { $('.rules p').hide(); $('#pp').slideToggle(); return false; });
$("#ct-a").click(function() { $('.rules p').hide(); $('#ct').slideToggle(); return false; });
$('.rules p').click(function() { $(this).slideToggle(); return false; });
