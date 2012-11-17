<?php

// http://www.rubblewebs.co.uk/imagemagick/GDexamples.php


switch($_GET['op']) :

case 'resize':
	$source = 'original.jpg';
	$destination = resize(200, $source);
	echo '<a href="', htmlspecialchars($source), '">Original Image</a> <a href="', htmlspecialchars($destination), '">New Image</a>';
break; //---------------------- resize

case 'watermark':
	$msg = substr($_GET['msg'], 0, 50);
	$source = 'original.jpg';
	$destination = watermark($msg);
	echo '<a href="', htmlspecialchars($source), '">Original Image</a> <a href="', htmlspecialchars($destination), '">New Image with [', $msg, ']</a>';

break; //---------------------- watermark

case 'phpinfo':
	echo phpinfo();
break;

default:
	echo 'You must pick an operation, op=:  resize, watermark';
break;

endswitch; // $_GET['op']


/**
 * Resize an image
 * @param  integer $dimension   [description]
 * @param  string  $source      [description]
 * @param  string  $destination [description]
 * @return [type]               [description]
 */
function resize($dimension = 200, $source = 'original.jpg', $destination = 'resized_%d.jpg'){
	// Set the path to the image to resize
	$input_image = $source;
	// Get the size of the original image into an array
	$size = getimagesize( $input_image );
	// Set the new width of the image
	$thumb_width = $dimension;
	// Calculate the height of the new image to keep the aspect ratio
	$thumb_height = ( int )(( $thumb_width/$size[0] )*$size[1] );
	// Create a new true color image in the memory
	$thumbnail = ImageCreateTrueColor( $thumb_width, $thumb_height );
	// Create a new image from file 
	$src_img = ImageCreateFromJPEG( $input_image );
	// Create the resized image
	ImageCopyResampled( $thumbnail, $src_img, 0, 0, 0, 0, $thumb_width, $thumb_height, $size[0], $size[1] );
	// Save the image as resized.jpg
	$destination = sprintf($destination, $dimension);
	ImageJPEG( $thumbnail, $destination );
	// Clear the memory of the tempory image 
	ImageDestroy( $thumbnail );

	return $destination;
}

function watermark($msg, $font_size = "30", $source = 'original.jpg'){
	// Create the canvas
	$canvas = imagecreate( 200, 100 );
	// Define the colours to use
	$black = imagecolorallocate( $canvas, 0, 0, 0 );  
	$white = imagecolorallocate( $canvas, 255, 255, 255 );  
	// Create a rectangle and fill it white
	imagefilledrectangle( $canvas, 0, 0, 200, 100, $white );  
	// The path to the font
	$font = "ERASMD.TTF"; //may not need extension? ".ttf";
	// The text to use 
	$text = $msg; 
	// The font size 
	$size = $font_size;
	// Set the path to the image to watermark
	$input_image = $source;	//"original.jpg"; 
	// Calculate the size of the text 
	// If php has been setup without ttf support this will not work.
	$box = imagettfbbox( $size, 0, $font, $text );  
	$x = (200 - ($box[2] - $box[0])) / 2;  
	$y = (100 - ($box[1] - $box[7])) / 2;  
	$y -= $box[7];  
	// Add the text to the image
	imageTTFText( $canvas, $size, 0, $x, $y, $black, $font, $text );  
	// Make white transparent
	imagecolortransparent ( $canvas, $white );  
	// Save the text image as temp.png
	imagepng( $canvas, "watermark.png" );  
	// Cleanup the tempory image canvas.png
	ImageDestroy( $canvas );  
	// Read in the text watermark image
	$watermark = imagecreatefrompng( "watermark.png" );  
	// Returns the width of the given image resource  
	$watermark_width = imagesx( $watermark );
	//Returns the height of the given image resource    
	$watermark_height = imagesy( $watermark );    
	$image = imagecreatetruecolor( $watermark_width, $watermark_height );    
	$image = imagecreatefromjpeg( $input_image );
	// Find the size of the original image and read it into an array      
	$size = getimagesize( $input_image );  
	// Set the positions of the watermark on the image
	$dest_x = $size[0] - $watermark_width - 100;    
	$dest_y = $size[1] - $watermark_height - 200;    
	imagecopymerge($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height, 50);   
	// Save the watermarked image as watermarked.jpg 
	$destination = "watermarked.jpg";
	imagejpeg( $image, $destination );
	// Clear the memory of the tempory image     
	imagedestroy( $image );    
	imagedestroy( $watermark );    
	// Delete the text watermark image
	//unlink( "temp.png");
	return $destination;
}

?>