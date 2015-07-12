<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/init.php';

?>

<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<title>Openstack Object Storage</title>

	<link rel="stylesheet" type="text/css" href="assets/styles.css"/>
	<link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'/>
	<link rel="icon" href="/assets/images/openstack_icon.png" type="image/png"/>

	<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="/assets/js/UIajax.js"></script>
</head>

<body>

<a href="/">
	<header>
		<?php echo $login_form ?>
	</header>
</a>

<div id="model">

	<h2 style="text-align: center;">Objectstorage Contents</h2>
	<?php print_r($renderedmodel); ?>
</div>

<div id="view">
</div>

<div id="controlpanel">
	
	<form class="controls" method="post" enctype="multipart/form-data" name="downloadByNameInput" id="downloadByNameInput">

		<input id="downloadByNameSubmit" type="submit" value="Download"/>

		<pre id="downloadByNameOutput"></pre>
	
	</form>	

	<form class="controls" method="post" enctype="multipart/form-data" name="deleteByNameInput" id="deleteByNameInput">

		<input id="deleteByNameSubmit" type="submit" value="Delete"/>

		<pre id="deleteByNameOutput"></pre>
	
	</form>

	<fieldset><legend>Upload files by name</legend>

		<form method="post" enctype="multipart/form-data" name="uploadByNameInput" id="uploadByNameInput">

			<label for="files">Select files for upload:</label> <input id="uploadfiles" type="file" name="files[]" multiple required/><br/>
			<label for="container">Upload to container:</label> <input type="text" name="container" required/><br/>
			<label for="folder">Upload to folder:</label> <input type="text" name="folder"/><br/>
			<input id="uploadByNameSubmit" type="submit" value="Upload"/>

			<pre id="uploadByNameOutput"></pre>
		
		</form>
	</fieldset>


</div>

</body>
</html>