<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

include('inc.php');




function logToFile($filename, $msg) {
	// http://www.devshed.com/c/a/PHP/Logging-With-PHP/1/#Tm1KhAGkW4islkJi.99 
	
	// open file
	$fd = fopen($filename, "a");
	// append date/time to message
	$str = "[" . date("Y/m/d h:i:s", mktime()) . "] " . $msg;
	// write string
	fwrite($fd, $str . "\n");
	// close file
	fclose($fd);
}


function showImages($label, $images) {
	$images = func_get_args();
	array_shift($images); // pop label

	?>
	<div class="image" id="<?php echo preg_replace('/[^a-zA-Z0-9]+/', '', $label); ?>">
		<h3><?php echo $label; ?></h3>
		<?php foreach($images as $src) : ?>
		<div class="image" style="position:relative;">
			<span style="position:absolute; bottom:0px; left:0px; opacity:0.5; background-color:white; color:black;"><b>Source:</b> <?php echo $src; ?></span>
			<img src="<?php echo $src ?>" />
		</div>
		<?php endforeach; ?>
		<hr />
	</div>
	<?php
}


// fake data
$json = '{ "shapes": [
	{
		"type" : "circle",
		"id" : "flxp-circle-0",
		"position" : {
			"x" : 120,
			"y" : 142
			},
		"size" : 25,
		"color" : "#FF0000"
	},
	{
		"type" : "triangle",
		"id" : "flxp-triangle-1",
		"position" : {
			"x" : 176,
			"y" : 215
			},
		"rotation" : 304,
		"color" : "#5F04B4"
	},
	{
		"type" : "star",
		"id" : "flxp-star-2",
		"position" : {
			"x" : 50,
			"y" : 187
			},
		"size" : 18,
		"rotation" : 48
	}
] }';
$data0 = json_decode($json, true);

# ==============================================


// defaults
$defaults = json_decode(file_get_contents('data/render-personalizer-defaults.json'), true);

// get post data
$data = array_merge($_GET, $_POST);

### add in some test data
$data = extend($data0, $data);

Debug::log(Debug::INFO, "Request", $data);

// merge with config defaults; respect "do-not-overwrite" commands
$data = extend($defaults, true, $data);

// configure debug logging
Debug::setLevel( (true == $data['config']['debug']) ? Debug::DEBUG : Debug::INFO );
Debug::log(Debug::DEBUG, "Defaults", $defaults);
Debug::log(Debug::DEBUG, 'configuration and input', $data);

// start image handling -----------------------

/*
// start with an Image, since resize punks it anyway?
$image = WideImage::load($asset);
// crush to expected output, saves processing later
// and wrap with manipulator
$wrapper = new WideImageWrapper(
	$image->resize($data['config']['output_size']['w'], $data['config']['output_size']['h']);
	);


 */

extract( getAssetFromResizedCache($data['config'], $data['asset']) );

Debug::log(Debug::INFO, 'Initializing image wrapper with asset + mask', $asset_path, $mask_path);
$wrapper = new WideImageWrapper($asset_path);

Debug::log(Debug::DEBUG, 'Looping instructions');
foreach($data['shapes'] as $i => $shape) {
	// adjust properties
	$position = rescale($data['config']['scale'], $shape['position']['x'], $shape['position']['y'], $data['config']['output_size']['w'], $data['config']['output_size']['h']);
	$dimensions = rescale(
		$data['config']['scale'],
		v($shape['size'], 0) + $data['config']['initial_shape_size']['w'],
		$data['config']['initial_shape_size']['h'],
		$data['config']['output_size']['w'],
		$data['config']['output_size']['h']
		);
	$color = substr(v($shape['color'], '#CCCCCC'), 1);
	$rotation = v($shape['rotation'], 0) / 360; // normalize

	Debug::log(Debug::DEBUG, 'Scaled to:', $position, $dimensions);

	$type = $shape['type'];
	switch( $type ) {
		case 'circle':
			$wrapper->$type(/*r*/$dimensions['x'], /*x*/$position['x'], /*y*/$position['y'], /*color*/$color );
			break;
		case 'heart':
		case 'square':
		case 'triangle':
			$wrapper->$type(/*r*/$dimensions['x'], /*x*/$position['x'], /*y*/$position['y'], /*color*/$color, $rotation );
			break;
		case 'star':
			$wrapper->$type(5, /*r*/$dimensions['x'], /*x*/$position['x'], /*y*/$position['y'], /*color*/$color, $rotation );
			break;
		case 'text':
			$wrapper->$type($shape['text'], /*x*/$position['x'], /*y*/$position['y'], array('hexColor'=>$color, 'rotation'=>$rotation) );
			break;
		// image is a weird case -- we might not be able to do it this way??? - seems that you have to call merge then save immediately
		case 'image':
			$overlay = WideImage::load($shape['src']);
			// scale overlay?
			$overlay->resize($shape['size']['w'], $shape['size']['h']);
			$wrapper->merge($overlay, $position['x']/*'50% - ' . $overlay->getWidth()*/, $position['y']/*'50% - ' . $overlay->getHeight()*//*, opacity */);
			// can't dispose until saved?  unset($overlay);
			break;
	}

}

// create guid and save
$newpath = sprintf('output/%s.%s', trim(com_create_guid(), '{}'), $data['config']['output_format']);

// overlay mask and save; for some reason you have to overlay image and save it immediately?
Debug::log(Debug::INFO, 'Merging mask overlay:', $mask_path);
Debug::log(Debug::INFO, 'Saving to path with quality:', $newpath, $data['config']['output_quality']);
$overlay = WideImage::load($mask_path);
$wrapper->merge($overlay)
	->saveToFile($newpath, $data['config']['output_quality']);

Debug::log(Debug::INFO, 'Shape complete');
$wrapper->dispose();
unset($overlay);


showImages('Finished Product', $asset_path, $newpath); # ================================

/*
// watermark, standard
$watermarkText = WideImage::load('watermark.png');
$img = WideImage::load('original.jpg')
	->resize('50%', '50%')
	->merge($watermarkText, '50% - ' . $watermarkText->getWidth(), '50% - ' . $watermarkText->getHeight(), 50)
	->saveToFile('watermarked-image.jpg');

showImages('WideImage-merge', 'watermarked-image.jpg'); //======================
*/


// // split test

// $wrapper = new WideImageWrapper('original.jpg');
// $wrapper
// 	// text -------------
// 	->setActiveFont('ERASMD.TTF', 25, 'FF6600')
// 	->text('testing wrapper1', 'left', 'bottom - 50', array('hexColor' => '00F6CC', 'rotation' => -0.75))
// 	->text('testing wrapper2', 'left', 'bottom - 20', array('hexColor' => '00F6CC', 'rotation' => 0.03, 'shadowOffset' => array(3,3) ))
// 	;
// $wrapper
// 	// parallelograms -------------
// 	->square(/*w*/'10%', /*x*/'left + 10%', /*y*/'middle', /*color*/'22C6F6')
// 		->text('1', /*x*/'left + 10%', /*y*/'middle')
// 	->rect(/*w*/'10%', /*h*/'20%', /*x*/'left + 10%', /*y*/'top + 10%', /*color*/'22F6C6', /*angle*/0.1)
// 		->text('2', /*x*/'left + 10%', /*y*/'top + 10%')
// 	->diamond(/*w*/'30%', /*h*/'10%', /*x*/'left + 30%', /*y*/'top + 20%', /*color*/'F833C8', /*angle*/(0.1+0.25))
// 		->text('3', /*x*/'left + 30%', /*y*/'top + 20%')
// 	->diamond(/*w*/'10%', /*h*/'30%', /*x*/'left + 30%', /*y*/'top + 20%', /*color*/'33F8C8', /*angle*/0.1)
// 		->text('4', /*x*/'left + 30%', /*y*/'top + 20%')
// 	;
// $wrapper
// 	// prove that triangle is just 3-pointed radial polygon -------------
// 	->triangle(/*r*/'20%', /*x*/'center + 25%', /*y*/'25%', /*color*/'7799AA')
// 		->text('5', /*x*/'center + 25%', /*y*/'25%')
// 	->star(3, /*r*/'20%', /*x*/'center + 25%', /*y*/'25%', /*color*/'99AA77', /*angle*/0.5)
// 		->text('6', /*x*/'center + 25%', /*y*/'25%')
// 	;
// 	// normal stars -------------
// $wrapper
// 	->star(5, /*r*/'20%', /*x*/'center', /*y*/'middle', /*color*/'F6C622')
// 		->text('7', /*x*/'center', /*y*/'middle')
// 	->star(6, /*r*/'100', /*x*/'right - 100', /*y*/'middle', /*color*/'C622F6')
// 		->text('8', /*x*/'right - 100', /*y*/'middle')
// 	->star(9, /*r*/'50', /*x*/'25%', /*y*/'75%', /*color*/'F60000')
// 		->text('9', /*x*/'25%', /*y*/'75%')
// 	;
// $wrapper
// 	->save('wideimagetest-wrapper-splitops.jpg')
// 	;

// showImages('Split Operation - wrapper', 'wideimagetest-wrapper-splitops.jpg'); // ==============================


// $wrapper = new WideImageWrapper('original.jpg');
// $wrapper
// 	->setActiveFont('ERASMD.TTF', 18, 'FF6600')
// 	->heart(/*r*/'100', /*x*/'25%', /*y*/'75%', /*color*/'F60000', 0.14)
// 		->text('heart 50 25% 75% 0.14', /*x*/'25%', /*y*/'75%')
// 	->heart(/*r*/'100', /*x*/'75%', /*y*/'75%', /*color*/'00F600', -0.36, '')
// 		->text('hollow heart', /*x*/'75%', /*y*/'75%')
// 	->circle(/*r*/'15%', /*x*/'75%', /*y*/'25%', /*color*/'00F600')
// 		->text('circle', /*x*/'75%', /*y*/'25%')
// 	->circle(/*r*/'15%', /*x*/'25%', /*y*/'25%', /*color*/'0000F6', 'ellipse')
// 		->text('hollow circle', /*x*/'25%', /*y*/'25%')
// 	->save('wideimagetest-wrapper-heart.jpg')
// 	;

// $wrapper->dispose();

// showImages('Wrapper - heart', 'wideimagetest-wrapper-heart.jpg'); // ==============================