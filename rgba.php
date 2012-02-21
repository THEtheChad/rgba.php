<?php
/***************************************************************************************
 * Script for automatic generation of one pixel
 * alpha-transparent images for non-RGBA browsers.
 * @author Lea Verou
 * @version 1.2
 * Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php)
 ***************************************************************************************/

######## SETTINGS ##############################################################

define('COLORDIR', dirname(__FILE__).'/rgba/');
define('SIZE', 10);

################################################################################

$dir = substr(COLORDIR, 0, strlen(COLORDIR) - 1);

if (!is_writable($dir)) {
	die("The directory '$dir' either doesn't exist or isn't writable.");
}

// rgba.php/rgba(R,G,B,A)
$color_info = explode(',', str_replace(' ', '', substr($_SERVER['PATH_INFO'], 6, -1)));
$red    = intval($color_info[0]);
$green  = intval($color_info[1]);
$blue   = intval($color_info[2]);
$alpha  = floatval($color_info[3]);

// A value between 0 and 127
$alpha = intval(127 - 127 * $alpha);

// Send headers
header('Content-type: image/png');
header('Expires: 01 Jan '.(date('Y') + 10).' 00:00:00 GMT');
header('Cache-control: max-age=2903040000');

$filepath = COLORDIR . "color_r{$red}_g{$green}_b{$blue}_a$alpha.png";

if(file_exists($filepath)) {

	// The file exists, is it cached by the browser?
	if (function_exists('apache_request_headers')) {
		$headers = apache_request_headers();

		// We don't need to check if it was actually modified since then as it never changes.
		$responsecode = isset($headers['If-Modified-Since'])? 304 : 200;
	}
	else {
		$responsecode = 200;
	}

	header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filepath)) . ' GMT', true, $responsecode);

	if ($responsecode == 200) {
		header('Content-Length: '.filesize($filepath));
		die(file_get_contents($filepath));
	}
}
else {
	$img = @imagecreatetruecolor(SIZE, SIZE)
		  or die('Cannot Initialize new GD image stream');

	// This is to allow the final image to have actual transparency
	// http://www.php.net/manual/en/function.imagesavealpha.php
	imagealphablending($img, false);
	imagesavealpha($img, true);

	// Allocate our requested color
	$color = imagecolorallocatealpha($img, $red, $green, $blue, $alpha);

	// Fill the image with it
	imagefill($img, 0, 0, $color);

    // Check PHP version to solve a bug that caused the script to fail on PHP versions < 5.1.7
    if (strnatcmp(phpversion(), '5.1.7') >= 0) {
        imagepng($img, $filepath, 0, NULL);
    }
    else {
        imagepng($img, $filepath);
    }

	// Serve the file
	imagepng($img);

	// Free up memory
	imagedestroy($img);
}