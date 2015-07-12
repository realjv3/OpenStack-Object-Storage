var uploadfiles = '';
var selection;

$(document).ready(function(){

	$('#model').on('click', '.up', function(event) {

		var formdata = new FormData();
		formdata.append('type', 'browseup');
		$('#model').html('<p>Updating...</p><img src="/assets/images/uploading.gif" styles=""margin-left: auto; margin-right: auto/>');

		//use ajax to call php script
		$.ajax({
			type: "POST",
			url: '/controller/browse.php',
			data: formdata,
			processData: false,
			contentType: false,
			success: function(resp) {
				//render the results in model div
				$('#model').html(resp);

				//bind click listener to newly created dir elements 
				$('.up').on('click');
			},
			error: function(req, status, error) {

				$('#uploadByNameOutput').html('Something went wrong.', status, error);
			}
		});
	});

	//bind listener to keep track of doubleclicked containers and folders to browse
	$('#model').on('dblclick', '.containers, .folders', function(event){

		$('#model').html('<p>Updating...</p><img src="/assets/images/uploading.gif" styles=""margin-left: auto; margin-right: auto/>');
		selection = '';
		$('#view').css('visibility', 'hidden');
		$('.controls').css('visibility', 'hidden');
		
		var formdata = new FormData();

		if ($(this).hasClass('containers')) {
			//get container name into POST with a FormData object
			var container = $(this).attr('id');

			formdata.append('type', 'browsedown');
			formdata.append('container', container);
			//use ajax to call php script
			$.ajax({

				type: "POST",
				url: '/controller/browse.php',
				data: formdata,
				processData: false,
				contentType: false,
				success: function(resp) {
					//render the results in model div
					$('#model').html(resp);
					//bind click listener to newly created dir elements 
					$('.containers, .folders, .files').on('dblclick');
				},
				error: function(req, status, error) {

					$('#uploadByNameOutput').html('Something went wrong.', status, error);
				}
			});
		};

		if ($(this).hasClass('folders')) {
			//get container name into POST with a FormData object
			var folder = $(this).attr('id');

			formdata.append('type', 'browsedown');
			formdata.append('folder', folder);

			//use ajax to call php script
			$.ajax({

				type: "POST",
				url: '/controller/browse.php',
				data: formdata,
				processData: false,
				contentType: false,
				success: function(resp) {
					//render the results in model div
					$('#model').html(resp);
					//bind click listener to newly created dir elements 
					$('.containers, .folders, .files').on('dblclick');
				},
				error: function(req, status, error) {

					$('#uploadByNameOutput').html('Something went wrong.', status, error);
				}
			});	
		};
	});

	//bind listener to keep track of clicked files to add/remove from selection
	$('#model').on('click', '.files', function(event){	
		//for files, if selecting, change data-selected attr & color of element
		if ($(this).attr('data-selected') == 0) {
			$(this).attr('data-selected', 1);
			$(this).css('background-color', '#C6C7CA');
			selection = $("div[data-selected = 1]").clone();
			selection.each(function(index, elem) {
				$(elem).attr('data-selected', "view");
			});
			//update view div with cloned selected elements
			$('#view').html(selection);
			//show view area and download/delete buttons if there's a selection, else hide	
			if (selection.length > 0) {
				$('#view').css('visibility', 'visible');
				$('.controls').css('visibility', 'visible');
			} else {
				$('#view').css('visibility', 'hidden');
				$('.controls').css('visibility', 'hidden');
			};
		} else {
		//if deselecting, change data-selected attr & color of element
			$(this).attr('data-selected', 0);
			$(this).css('background-color', '#E2E2E2');
			//get selected files and save in an array
			selection = $("div[data-selected = 1]").clone();
			selection.each(function(index, elem) {
				$(elem).attr('data-selected', "view");
			});
			//update view div with cloned selected elements
			$('#view').html(selection);
			//show view area and download/delete buttons if there's a selection, else hide	
			if (selection.length > 0) {
				$('#view').css('visibility', 'visible');
				$('.controls').css('visibility', 'visible');
			} else {
				$('#view').css('visibility', 'hidden');
				$('.controls').css('visibility', 'hidden');
			};
		};
	});

	//once files to upload have been selected, save absolute path for files in uploadfiles
	$('#uploadfiles').change( function() {

		uploadfiles = $('#uploadfiles').val();

		//change the input type to text and fill absolute paths back in
		//this avoids using $_FILES for uploading b/c we're using curl with the absolute paths instead
		$('#uploadfiles').attr("type", "text").attr("name", "uploadfiles").removeAttr("required").removeAttr("multiple").val(uploadfiles);
	});

	//bind listener and set ajax for upload by file name UI
	$('#uploadByNameInput').on('submit', function(event) {

		event.preventDefault();
		$('#uploadByNameOutput').html('<p>Uploading...</p><img src="/assets/images/uploading.gif" styles=""margin-left: auto; margin-right: auto/>');
		
		var formdata = new FormData($('#uploadByNameInput')[0]);
		
		formdata.append('type', 'upload');
		
		$.ajax({

			type: "POST",
			url: '/controller/upload.php',
			data: formdata,
			processData: false,
			contentType: false,
			timeout: 86400000,
			success: function(resp) {

				$('#uploadByNameOutput').html(resp);
			},
			error: function(req, status, error) {

				$('#uploadByNameOutput').html('Something went wrong.', status, error);
			}
		});
	});

	//bind listener and set ajax for download by file name UI
	$('#downloadByNameInput').on('submit', function(event) {
		// output the downloading moving gif
		event.preventDefault();
		$('#downloadByNameOutput').html('<p>Downloading...</p><img src="/assets/images/uploading.gif" styles=""margin-left: auto; margin-right: auto/>');
		
		//append the selection array and request type to FormData object
		var downloadfiles = [];
		var formdata = new FormData();
		formdata.append('type', 'download');
		$(selection).each(function(index, elem) {
			var filename = $(elem).attr('id');
			filename = filename.split(" ");
			filename.splice(-5, filename.length);
			filename = filename.join(' ');
			
			downloadfiles.push(filename);
			formdata.append('downloadfiles', downloadfiles);
		});

		$.ajax({

			type: "POST",
			url: '/controller/download.php',
			data: formdata,
			processData: false,
			contentType: false,
			timeout: 86400000,
			success: function(resp) {

				$('#downloadByNameOutput').html(resp);
				selection = '';
				$('#view').css('visibility', 'hidden');
			},
			error: function(req, status, error) {

				$('#downloadByNameOutput').html('Something went wrong.', status, error);
			}
		});
	});	

	//bind listener and set ajax for download by file name UI
	$('#deleteByNameInput').on('submit', function(event) {
		// output the downloading moving gif
		event.preventDefault();
		$('#deleteByNameOutput').html('<p>Deleting...</p><img src="/assets/images/uploading.gif" styles=""margin-left: auto; margin-right: auto/>');
		
		//append the selection array and request type to FormData object
		var deletefiles = [];
		var formdata = new FormData();
		formdata.append('type', 'delete');
		$(selection).each(function(index, elem) {
			var filename = $(elem).attr('id');
			filename = filename.split(" ");
			filename.splice(-5, filename.length);
			filename = filename.join(' ');
			deletefiles.push(filename);
			formdata.append('deletefiles', deletefiles);
		});

		$.ajax({

			type: "POST",
			url: '/controller/delete.php',
			data: formdata,
			processData: false,
			contentType: false,
			success: function(resp) {

				$('#deleteByNameOutput').html(resp);
				selection = '';
				$('#view').css('visibility', 'hidden');
			},
			error: function(req, status, error) {

				$('#deleteByNameOutput').html('Something went wrong.', status, error);
			}
		});
	});
});