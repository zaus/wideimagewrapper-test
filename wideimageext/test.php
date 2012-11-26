<?php
include('../inc.php');



include('GD_Utils.class.php');

?>
<style>
	* { box-sizing:border-box; }

	.coord {
		width:202px;
		height:202px;
		position:relative;
		border:1px solid black;
	}
	.coord:before {
		content:'';
		display:block; width:50%; height:50%;
		position:absolute; top:0; left:0;
		border:1px dotted #777;
		border-top:none;
		border-left:none;
	}
	.coord:after {
		content:'';
		display:block; width:50%; height:50%;
		position:absolute; bottom:0; right:0;
		border:1px dotted #777;
		border-right:none;
		border-bottom:none;
	}
</style>
<?php
function scale($x, $y) {
	// flip orientation
	$y *= -1;

	$width = $height = 200.0;
	return array(
		(float)$x * $width / 10.0 - 1.5 + ($width/2.0),
		(float)$y * $height / 10.0 - 1.5 + ($height/2.0)
		);
}

function doPoints($x, $y) {
	list($x1, $y1) = scale($x, $y);
	$radius = GD_Utils::magnitude($x, $y);
	list($r, $a) = GD_Utils::polar($x, $y);
	?>
	<article>
		<div class="coord">
			<span style="display:block; position:absolute; height:3px; width:3px; left:<?php echo $x1; ?>px; top:<?php echo $y1; ?>px;">o</span>
		</div>
		<p>x = <?php echo $x; ?>, y = <?php echo $y; ?></p>
		<p>r = <?php echo $r; ?>, a = <?php echo $a; ?> = <?php echo $a * 360; ?> deg</p>
		<p><em>(x' = <?php echo $x1; ?>, y' = <?php echo $y1; ?>)</em></p>
	<?php

	// soh cah toa
	// sin = opp / hyp = 4/5
	// cos = adj / hyp = 3/5
	echo '<em>radius:', $r, ' acos:', acos($x/$r), ' asin:', asin($y/$r), ' acos:', rad2deg(acos($x/$r)), ' asin:', rad2deg(asin($y/$r)), '</em>';
	?>
	</article>
	<?php
}

$x = isset($_REQUEST['x']) ? intval($_REQUEST['x']) : 3;
$y = isset($_REQUEST['y']) ? intval($_REQUEST['y']) : 4;

doPoints($x, $y);
doPoints(-$x, $y);
doPoints($x, -$y);
doPoints(-$x, -$y);

$center_x = 0;
$center_y = 0;
$width = 100;
$rotation = 0;

$star1 = GD_Utils::star5($center_x, $center_y, $width, $rotation = 0);

pbug($star1);