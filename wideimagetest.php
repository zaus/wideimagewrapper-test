<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

include('wideimage/lib/WideImage.php');
include('inc.php');

function showImages($label, $images) {
	$images = func_get_args();
	array_shift($images); // pop label

	?>
	<div class="image">
		<h3><?php echo $label; ?></h3>
		<?php foreach($images as $src) : ?>
		<img src="<?php echo $src ?>" />
		<?php endforeach; ?>
		<hr />
	</div>
	<?php
}


/**
 * Calculate color with alpha transparency without using image library.
 * @see http://us2.php.net/manual/en/function.imagecolorallocatealpha.php#79486
 * @param  string $hexColor regular hex notation like 'FF6600'
 * @param  float $alpha    transparency as percent: 0 to 1.0; mapped to 0 (opaque) - 127 (transparent)
 * @return [type]           [description]
 */
function colorFromHex($hexColor,$alpha = 0) {
	return bindec(decbin( (float)$alpha*127 ) . decbin(hexdec($hexColor)));
}

/**
 * Write text to a canvas with the given properties
 * @param  string $msg      the message to write
 * @param  mixed $x        smart coordinate, x
 * @param  mixed $y        smart coordinate, y
 * @param  int $shadowX       shadow X offset
 * @param  int $shadowY       shadow Y offset
 * @param  string $font     path to font
 * @param  int $fontsize font size
 * @param  string $hexColor    CSS hex color representation
 * @param  obj $canvas   WideImage canvas; include by reference
 * @return void           n/a
 */
function textWithShadow($msg, $x, $y, $shadowX, $shadowY, $font, $fontsize, $hexColor, &$canvas) {
	// set the active font
	$font = $canvas->useFont($font, $fontsize, colorFromHex($hexColor));

	// draw text in bottom corner
	$canvas->writeText($x, $y, $msg);

	// draw text again with offset, in different color for shadow effect
	$font->color = colorFromHex($hexColor, 0.70);
	$canvas->writeText(
		$x . $shadowX > 0 ? ' + '.$shadowX : $shadowX
		, $y . $shadowY > 0 ? ' + '.$shadowY : $shadowY, $msg);
}//--	fn	textWithShadow

function array_flatten($array) {
	$result = array();
	foreach($array as $k => $v) {
		if( is_array($v) ) {
			$result = array_merge($result, array_flatten($v));
		}
		else {
			$result []= $v;
		}
	}
	return $result;
}

showImages('Original', 'original.jpg'); //======================

// load the original image
$image = WideImage::load('original.jpg');

// get the canvas for drawing
$canvas = $image->getCanvas();

textWithShadow('Foo bar', 'left', 'top', 2, 2, 'ERASMD.TTF', 25, 'FF6600', $canvas);


$image->saveToFile('wideimagetest.jpg');

showImages('Text with Shadow - func', 'wideimagetest.jpg'); //======================

// watermark, standard
$watermarkText = WideImage::load('watermark.png');
$img = WideImage::load('original.jpg')
	->resize('50%', '50%')
	->merge($watermarkText, '50% - ' . $watermarkText->getWidth(), '50% - ' . $watermarkText->getHeight(), 50)
	->saveToFile('watermarked-image.jpg');

showImages('WideImage-merge', 'watermarked-image.jpg'); //======================

/**/
include('wideimageext/WideImageWrapper.class.php');

$shapes = array();
$wrapper = new WideImageWrapper('original.jpg');
$wrapper->setActiveFont('ERASMD.TTF', 25, 'FF6600'); // default font
$wrapper->text('Foo bar', 'left', 'top', array('hexColor'=>'FF6600', 'shadowAlpha' => 0.70, 'shadowOffset'=>array(2, 2)));
$wrapper->canvas->polygon( GD_Utils::star5(300, 300, 200), 5, GD_Utils::rgba('FF0000') ); // draw 5-pointed star
$shapes []= $wrapper->star( 5, 200, 500, 200, 0, GD_Utils::rgba('00FF00') ); // fill 5-pointed star
$shapes []= $wrapper->star( 11, 200, 800, 300, 0, GD_Utils::rgba('00FF00') ); // fill 11-pointed star
$shapes []= $wrapper->star( 7, 100, 100, 100, 0, GD_Utils::rgba('F0F000') ); // 7 pointed star upper left
$shapes []= $wrapper->star( 8, 100, 100, 300, 0, GD_Utils::rgba('FF6600') ); // 8 pointed star...not working well
$shapes []= $wrapper->star( 6, 100, 200, 300, 0, GD_Utils::rgba('FF0066') ); // 8 pointed star...not working well
$shapes []= $wrapper->star( 10, 100, 300, 100, 0, GD_Utils::rgba('FF6600') ); // 10 pointed star
$wrapper->save('wideimagetest.class.jpg', 60);

showImages('WideImageWrapper', 'wideimagetest.class.jpg'); //======================
#pbug($shapes);

// overlay
$watermark_blah = new WideImageWrapper('watermark.png');

$watermark = new WideImageWrapper(false);
$watermark->setImage(WideImage_TrueColorImage::create(100, 100));
$watermark->makeTransparent(); //$watermark->image->fill(0,0,$watermark->image->getTransparentColor());
//$watermark->image->allocateColorAlpha(255,255,255,100);
//$watermark->image->colorTransparent( GD_Utils::rgba('000000', 1));

$watermark2 = WideImageWrapper::copy($watermark);
$watermark->star(5, 100, 'center', 'center', 0, GD_Utils::rgba('FF0000'));
$watermark->save('watermark-star.png');

$watermark2->star(5, 100, '50%', '50%', 0, GD_Utils::rgba('00FF00'));
$watermark2->save('watermark-star2.png');

showImages('Transparent Watermarks', 'watermark-star.png', 'watermark-star2.png'); //======================

// http://wideimage.sourceforge.net/examples/merge-watermark/
$wrapper = new WideImageWrapper('wideimagetest.class.jpg');
pbug('wrapper object image - for watermark attempt', $wrapper->image);
$wrapper->image->merge($watermark->image);
$wrapper->image->merge($watermark->image, '50%', '20%', 50);
$wrapper->image->merge($watermark_blah->image, '40%', '30%', 50);
$wrapper->image->merge($watermarkText, 'right', 'top');
$wrapper->save('wideimagetest-watermarked.class.jpg');

pbug('wrapper object, after actions', $wrapper);

showImages('Merged wrapper', 'wideimagetest-watermarked.class.jpg'); //======================

// $img->merge($watermark->image)->saveToFile('img-with-starwatermark.jpg', 70);
// showImages('merged half-and-half', 'img-with-starwatermark.jpg'); //======================

// explicitly dispose
$wrapper->dispose();
$watermark_blah->dispose();
