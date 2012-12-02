<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

include('wideimage/lib/WideImage.php');
include('inc.php');

function showImages($label, $images) {
	$images = func_get_args();
	array_shift($images); // pop label

	?>
	<div class="image" id="<?php echo preg_replace('/[^a-zA-Z0-9]+/', '', $label); ?>">
		<h3><?php echo $label; ?></h3>
		<?php foreach($images as $src) : ?>
		<div class="image" style="position:relative;">
			<span style="position:absolute; bottom:0px; left:0px; opacity:0.5;"><b>Source:</b> <?php echo $src; ?></span>
			<img src="<?php echo $src ?>" />
		</div>
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


# ==================================================
# ============= WIDE IMAGE WRAPPER =================
# ==================================================

include('wideimageext/WideImageWrapper.class.php');

// watermark, standard
$watermarkTextWrapped = new WideImageWrapper('watermark.png');
$imgWrapped = new WideImageWrapper('original.jpg');
$imgWrapped
	->resize('50%', '50%')
	->merge($watermarkTextWrapped->image, '50% - ' . $watermarkTextWrapped->image->getWidth(), '50% - ' . $watermarkTextWrapped->image->getHeight(), 50)
	->saveToFile('watermarked-image-wrapped.jpg');

showImages('WideImageWrapper-merge', 'watermarked-image-wrapped.jpg'); //======================


$shapes = array();
$wrapper = new WideImageWrapper('original.jpg');
$wrapper->setActiveFont('ERASMD.TTF', 25, 'FF6600'); // default font
$wrapper->text('Foo bar', 'left', 'top', array('hexColor'=>'FF6600', 'shadowAlpha' => 0.70, 'shadowOffset'=>array(2, 2)));
$wrapper->canvas->polygon( GD_Utils::star5(300, 300, 200), 5, GD_Utils::rgba('FF0000') ); // draw 5-pointed star
$shapes []= $wrapper->star( 5, 200, 500, 200, GD_Utils::rgba('00FF00'), 0, 'filledpolygon', true ); // fill 5-pointed star
$shapes []= $wrapper->star( 11, 200, 800, 300, GD_Utils::rgba('00FF00'), 0, 'filledpolygon', true ); // fill 11-pointed star
$shapes []= $wrapper->star( 7, 100, 100, 100, GD_Utils::rgba('F0F000'), 0, 'filledpolygon', true ); // 7 pointed star upper left
$shapes []= $wrapper->star( 8, 100, 100, 300, GD_Utils::rgba('FF6600'), 0, 'filledpolygon', true ); // 8 pointed star...not working well
$shapes []= $wrapper->star( 6, 100, 200, 300, GD_Utils::rgba('FF0066'), 0, 'filledpolygon', true ); // 8 pointed star...not working well
$shapes []= $wrapper->star( 10, 100, 300, 100, GD_Utils::rgba('FF6600'), 0, 'filledpolygon', true ); // 10 pointed star
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
$watermark->star(5, '50%', 'center', 'center', GD_Utils::rgba('FF0000'));
$watermark->save('watermark-star.png');

$watermark2->star(5, 100, '50%', '50%', GD_Utils::rgba('00FF00'));
$watermark2->save('watermark-star2.png');

showImages('Transparent Watermarks', 'watermark-star.png', 'watermark-star2.png'); //======================

// http://wideimage.sourceforge.net/examples/merge-watermark/
$wrapper = new WideImageWrapper('original.jpg');
// different styles of function calls -- fallthrough to WideImage method
pbug('atomic save');
$wrapper->merge($watermark->image);
$wrapper->saveToFile('wideimagetest-watermarked-atomic.jpg');
pbug('straight up save');
$wrapper
	->merge($watermark->image, '50%', '20%', 50)
	->saveToFile('wideimagetest-watermarked.class.jpg');
pbug('wrapper save');
$wrapper->save('wideimagetest-watermarked2.class.jpg');
/*
// different styles of function calls -- explicitly call WideImage method
$wrapper->image
	//->merge($watermark_blah->image, '40%', '30%', 50)
	->merge($watermarkText, 'right', 'top');
$wrapper->save->saveToFile('wideimagetest-watermarked.class.jpg');
*/

showImages('Merged wrapper with original', 'original.jpg', 'wideimagetest-watermarked-atomic.jpg', 'wideimagetest-watermarked.class.jpg', 'wideimagetest-watermarked2.class.jpg'); //======================

// http://wideimage.sourceforge.net/examples/merge-watermark/
$wrapper = new WideImageWrapper('original.jpg');
// different styles of function calls -- explicitly call WideImage method
$wrapper
	->merge($watermark->image)
	->merge($watermark->image, '50%', '20%', 50)
	->merge($watermarkText, 'right', 'top')
	->saveToFile('wideimagetest-wrapper-watermarked.class.jpg', 93);

showImages('Wrapper-merging watermarks', 'original.jpg', 'wideimagetest-wrapper-watermarked.class.jpg'); //======================

// $img->merge($watermark->image)->saveToFile('img-with-starwatermark.jpg', 70);
// showImages('merged half-and-half', 'img-with-starwatermark.jpg'); //======================

// explicitly dispose
$wrapper->dispose();
$watermark_blah->dispose();

// http://wideimage.sourceforge.net/examples/merge-watermark/
$g = WideImage::load('original.jpg');
$g
	->merge($watermark->image)
	->merge($watermark->image, '50%', '20%', 50)
	->merge($watermarkText, 'right', 'top')
	->saveToFile('wideimagetest-alone-watermarked.class.jpg', 93);

showImages('Standalone-merging watermarks', 'wideimagetest-alone-watermarked.class.jpg'); //======================


$wrapper = new WideImageWrapper('original.jpg');
$wrapper
	->setActiveFont('ERASMD.TTF', 25, 'FF6600')
	->text('testing wrapper1', 'left', 'bottom - 50', array('hexColor' => '00F6CC', 'rotation' => -0.75))
	->text('testing wrapper2', 'left', 'bottom - 20', array('hexColor' => '00F6CC', 'rotation' => 0.03, 'shadowOffset' => array(3,3) ))
	->save('wideimagetest-wrapper-text.jpg')
	;

showImages('Wrapper text', 'wideimagetest-wrapper-text.jpg'); // ==============================

// full test

$wrapper = new WideImageWrapper('original.jpg');
$wrapper
	// text -------------
	->setActiveFont('ERASMD.TTF', 25, 'FF6600')
	->text('testing wrapper1', 'left', 'bottom - 50', array('hexColor' => '00F6CC', 'rotation' => -0.75))
	->text('testing wrapper2', 'left', 'bottom - 20', array('hexColor' => '00F6CC', 'rotation' => 0.03, 'shadowOffset' => array(3,3) ))
	// parallelograms -------------
	->square(/*w*/'10%', /*x*/'left + 10%', /*y*/'middle', /*color*/'22C6F6')
		->text('1', /*x*/'left + 10%', /*y*/'middle')
	->rect(/*w*/'10%', /*h*/'20%', /*x*/'left + 10%', /*y*/'top + 10%', /*color*/'22F6C6', /*angle*/0.1)
		->text('2', /*x*/'left + 10%', /*y*/'top + 10%')
	->diamond(/*w*/'30%', /*h*/'10%', /*x*/'left + 30%', /*y*/'top + 20%', /*color*/'F833C8', /*angle*/(0.1+0.25))
		->text('3', /*x*/'left + 30%', /*y*/'top + 20%')
	->diamond(/*w*/'10%', /*h*/'30%', /*x*/'left + 30%', /*y*/'top + 20%', /*color*/'33F8C8', /*angle*/0.1)
		->text('4', /*x*/'left + 30%', /*y*/'top + 20%')
	// prove that triangle is just 3-pointed radial polygon -------------
	->triangle(/*r*/'20%', /*x*/'center + 25%', /*y*/'25%', /*color*/'7799AA')
		->text('5', /*x*/'center + 25%', /*y*/'25%')
	->star(3, /*r*/'20%', /*x*/'center + 25%', /*y*/'25%', /*color*/'99AA77', /*angle*/0.5)
		->text('6', /*x*/'center + 25%', /*y*/'25%')
	// normal stars -------------
	->star(5, /*r*/'20%', /*x*/'center', /*y*/'middle', /*color*/'F6C622')
		->text('7', /*x*/'center', /*y*/'middle')
	->star(6, /*r*/'100', /*x*/'right - 100', /*y*/'middle', /*color*/'C622F6')
		->text('8', /*x*/'right - 100', /*y*/'middle')
	->star(9, /*r*/'50', /*x*/'25%', /*y*/'75%', /*color*/'F60000')
		->text('9', /*x*/'25%', /*y*/'75%')
	->save('wideimagetest-wrapper-shapes.jpg')
	;

$wrapper->dispose();
showImages('All Shapes - render example', 'wideimagetest-wrapper-shapes.jpg'); // ==============================


// split test

$wrapper = new WideImageWrapper('original.jpg');
$wrapper
	// text -------------
	->setActiveFont('ERASMD.TTF', 25, 'FF6600')
	->text('testing wrapper1', 'left', 'bottom - 50', array('hexColor' => '00F6CC', 'rotation' => -0.75))
	->text('testing wrapper2', 'left', 'bottom - 20', array('hexColor' => '00F6CC', 'rotation' => 0.03, 'shadowOffset' => array(3,3) ))
	;
$wrapper
	// parallelograms -------------
	->square(/*w*/'10%', /*x*/'left + 10%', /*y*/'middle', /*color*/'22C6F6')
		->text('1', /*x*/'left + 10%', /*y*/'middle')
	->rect(/*w*/'10%', /*h*/'20%', /*x*/'left + 10%', /*y*/'top + 10%', /*color*/'22F6C6', /*angle*/0.1)
		->text('2', /*x*/'left + 10%', /*y*/'top + 10%')
	->diamond(/*w*/'30%', /*h*/'10%', /*x*/'left + 30%', /*y*/'top + 20%', /*color*/'F833C8', /*angle*/(0.1+0.25))
		->text('3', /*x*/'left + 30%', /*y*/'top + 20%')
	->diamond(/*w*/'10%', /*h*/'30%', /*x*/'left + 30%', /*y*/'top + 20%', /*color*/'33F8C8', /*angle*/0.1)
		->text('4', /*x*/'left + 30%', /*y*/'top + 20%')
	;
$wrapper
	// prove that triangle is just 3-pointed radial polygon -------------
	->triangle(/*r*/'20%', /*x*/'center + 25%', /*y*/'25%', /*color*/'7799AA')
		->text('5', /*x*/'center + 25%', /*y*/'25%')
	->star(3, /*r*/'20%', /*x*/'center + 25%', /*y*/'25%', /*color*/'99AA77', /*angle*/0.5)
		->text('6', /*x*/'center + 25%', /*y*/'25%')
	;
	// normal stars -------------
$wrapper
	->star(5, /*r*/'20%', /*x*/'center', /*y*/'middle', /*color*/'F6C622')
		->text('7', /*x*/'center', /*y*/'middle')
	->star(6, /*r*/'100', /*x*/'right - 100', /*y*/'middle', /*color*/'C622F6')
		->text('8', /*x*/'right - 100', /*y*/'middle')
	->star(9, /*r*/'50', /*x*/'25%', /*y*/'75%', /*color*/'F60000')
		->text('9', /*x*/'25%', /*y*/'75%')
	;
$wrapper
	->save('wideimagetest-wrapper-splitops.jpg')
	;

showImages('Split Operation - wrapper', 'wideimagetest-wrapper-splitops.jpg'); // ==============================


$wrapper = new WideImageWrapper('original.jpg');
$wrapper
	->setActiveFont('ERASMD.TTF', 18, 'FF6600')
	->heart(/*r*/'100', /*x*/'25%', /*y*/'75%', /*color*/'F60000', 0.14)
		->text('heart 50 25% 75% 0.14', /*x*/'25%', /*y*/'75%')
	->heart(/*r*/'100', /*x*/'75%', /*y*/'75%', /*color*/'00F600', -0.36, '')
		->text('hollow heart', /*x*/'75%', /*y*/'75%')
	->circle(/*r*/'15%', /*x*/'75%', /*y*/'25%', /*color*/'00F600')
		->text('circle', /*x*/'75%', /*y*/'25%')
	->circle(/*r*/'15%', /*x*/'25%', /*y*/'25%', /*color*/'0000F6', 'ellipse')
		->text('hollow circle', /*x*/'25%', /*y*/'25%')
	->save('wideimagetest-wrapper-heart.jpg')
	;

$wrapper->dispose();

showImages('Wrapper - heart', 'wideimagetest-wrapper-heart.jpg'); // ==============================


$wrapper = new WideImageWrapper('original.jpg');
$overlay = WideImage::load('renderer/assets/MyFlexi_Large_Mask_800_600.png');
$wrapper->merge($overlay)
	->saveToFile('wideimagetest-wrapper-mergerenderermask.jpg');
$wrapper->dispose();
unset($overlay);
showImages('Wrapper - renderer overlay', 'wideimagetest-wrapper-mergerenderermask.jpg'); // ==============================

$wrapper = new WideImageWrapper('original.jpg');
$overlay = WideImage::load('renderer/assets/MyFlexi_Large_Mask_800_600.png');
$wrapper->merge($overlay);
$wrapper->save('wideimagetest-wrapper-mergerenderermask2.jpg');
unset($overlay);
$wrapper->dispose();
showImages('Wrapper - renderer overlay ORDER SWITCHED', 'wideimagetest-wrapper-mergerenderermask2.jpg'); // ==============================

$wrapper = new WideImageWrapper('original.jpg');
$overlay = WideImage::load('renderer/assets/MyFlexi_Large_Mask_800_600.png');
$img = $wrapper->merge($overlay);
$wrapper
	->setActiveFont('ERASMD.TTF', 18, 'FF6600')
	->circle(/*r*/'15%', /*x*/'25%', /*y*/'25%', /*color*/'0000F6', 'ellipse')
		->text('hollow circle', /*x*/'25%', /*y*/'25%')
	;
$img->saveToFile('wideimagetest-wrapper-mergerenderermask3.jpg');
unset($overlay);
$wrapper->dispose();
showImages('Wrapper - renderer overlay ORDER SWITCHED 2', 'wideimagetest-wrapper-mergerenderermask3.jpg'); // ==============================

# ================== helper, not wrapper ================

/**/
include('wideimageext/WideImage_Helper.php');

pbug('###setting font');
WideImage_Helper::font($g, 'ERASMD.TTF', 25, 'FF6600');
pbug('###font set, drawing text');
WideImage_Helper::text($g, 'testing wrapper1', 'left', 'bottom - 50', array('hexColor' => '00F6CC', 'rotation' => -0.75));
pbug('###text1 drawn, drawing text 2');
WideImage_Helper::text($g, 'testing wrapper2', 'left', 'bottom - 20', array('hexColor' => '00F6CC', 'rotation' => 0.03, 'shadowOffset' => array(3,3) ));
pbug('###text2 drawn, saving');

$g->saveToFile('wideimagetest-helper-text.jpg', 80);
showImages('Helper text', 'wideimagetest-helper-text.jpg');
