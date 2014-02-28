<?php

require_once( 'classes/Griddle.php' );
$griddle = new Griddle();

if ( isset($_REQUEST['sizes']) && !empty($_REQUEST['sizes']) ) {

	$griddle->process_multiple(
		$_REQUEST['sizes'],
		isset( $_REQUEST['download'] )
	);

} elseif ( isset($_REQUEST['width']) && !empty($_REQUEST['width']) && isset($_REQUEST['height']) && !empty($_REQUEST['height']) ) {

	$griddle->process_single(
		$_REQUEST['width'],
		$_REQUEST['height'],
		isset( $_REQUEST['download'] )
	);

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Griddle Test Image Generator</title>
	<meta name="description" value="Render test images with a grid and center lines for use in testing uploads, cropping and resizing of images.">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="bootstrap/css/bootstrap.css" rel="stylesheet">
	<style type="text/css">
	body {
		padding-top: 40px;
		padding-bottom: 40px;
		background-color: #f5f5f5;
	}

	.form-signin {
		max-width: 300px;
		padding: 19px 29px 29px;
		margin: 0 auto 20px;
		background-color: #fff;
		border: 1px solid #e5e5e5;
		-webkit-border-radius: 5px;
		 -moz-border-radius: 5px;
		      border-radius: 5px;
		-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
		 -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
		      box-shadow: 0 1px 2px rgba(0,0,0,.05);
	}
	.form-signin .form-signin-heading,
	.form-signin .checkbox {
		margin-bottom: 10px;
		margin-top: 10px;
	}

	.form-signin textarea {
		width: 286px;
		font-size: 25px;
	}

	.form-signin input[type="number"] {
		height: 40px;
		font-size: 30px;
		width: 90px;
		line-height: 34px;
	}

	.form-signin .btn {
		width: 100%;
	}

	</style>
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<!--[if lt IE 9]>
	      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	    <![endif]-->
</head>
<body>
	<div class="container">
	<?php
	if ( !empty($griddle->errors) ) {
		foreach ($griddle->errors as $error) {
			echo '<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">×</button>';
			echo "$error";
			echo '</div>';
		}
	}
	?>
	<form class="form-signin">
		<h2 class="form-signin-heading">Generate an Image</h2>
		<div class="form-inline"><input type="number" name="width" placeholder="300" />
		X
		<input type="number" name="height" placeholder="250" /></div>
		<label class="checkbox">
			<input type="checkbox" name="download" value="1" checked> Download
		</label>
		<button class="btn btn-large btn-primary" type="submit">Generate!</button>
	</form>
	<form class="form-signin">
		<h2 class="form-signin-heading">Generate a Bunch!</h2>
		<textarea name="sizes" rows="5" placeholder="width,height"></textarea>
		<input type="hidden" name="download" value="1">
		<button class="btn btn-large" type="submit">Download the ZIP File!</button>
	</form>
	</div>
</body>
</html>