<?php
error_reporting( E_ALL );
ini_set( 'display_errors', 1 );

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
pbug('test data', $data0);

# ==============================================

include('wideimageext/WideImage_Helper.php');

// defaults
$defaults = json_decode(file_get_contents('data/render-personalizer-defaults.json'), true);
pbug("default options", $defaults);


// get post data
$data = array_merge($_GET, $_POST);

### add in some test data
$data = array_merge_recursive($data0, $data);

// merge with config defaults
$data = array_merge_recursive($defaults, $data);

pbug('sample data + config', $data);



/*
// watermark, standard
$watermarkText = WideImage::load('watermark.png');
$img = WideImage::load('original.jpg')
	->resize('50%', '50%')
	->merge($watermarkText, '50% - ' . $watermarkText->getWidth(), '50% - ' . $watermarkText->getHeight(), 50)
	->saveToFile('watermarked-image.jpg');

showImages('WideImage-merge', 'watermarked-image.jpg'); //======================
*/