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
	.point {
		display:block; height:5px; width:5px;
		background-color: black; 
		position:absolute;
		border-radius:3px;
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

function calcPoints($x, $y) {
	list($x1, $y1) = scale($x, $y);
	list($r, $a) = GD_Utils::polar($x, $y);

	return array(
		'x' => $x, 'y' => $y,
		'x1' => $x1, 'y1' => $y1,
		'r' => $r, 'a' => $a,
	);
}

function num($v, $p = 2) {
	return round($v, $p);
}


function doPoints($x, $y) {
	if( !is_array($x) ) $x = (array)$x;
	if( !is_array($y) ) $y = (array)$y;

	$values = array();
	foreach($x as $n => $i) {
	##	foreach($y as $j) {
			$values []= calcPoints($i, $y[$n]);
	##	}
	}

	?>
	<article>
		<div class="coord">
			<?php foreach($values as $n => $v) :
				extract($v);
				?>
				<span class="point" style="background-color:rgb(<?php echo round($x1/200 * 255); ?>, <?php echo round($y1/200 * 255); ?>, 0); left:<?php echo $x1; ?>px; top:<?php echo $y1; ?>px;"></span>
			<?php endforeach; ?>
		</div>
		<?php foreach($values as $n => $v) :
		extract($v); ?>
		<p>
			x = <?php echo num($x); ?>, y = <?php echo num($y); ?> <em>(x' = <?php echo num($x1); ?>, y' = <?php echo num($y1); ?>)</em>
				&rarr; r = <?php echo num($r); ?>, a = <?php echo num($a); ?> = <?php echo num($a * 360); ?> deg
				<?php list($x2, $y2) = GD_Utils::cartesian($r, $a); ?>
				&rarr; x2 = <?php echo num($x2); ?>, y2 = <?php echo num($y2); ?>
				<?php
				if( num($x) != num($x2) ) echo ' x&rarr;x2 fail';
				if( num($y) != num($y2) ) echo ' y&rarr;y2 fail';
				?>
			<br />
			<em>atan2(<?php echo num($y); ?>, <?php echo num($x) ?>) = <?php echo num( GD_Utils::radToPercent(atan2($y, $x)) * 360 ) ?></em> <b>vs.</b> <em>atan(<?php echo num($y); ?> / <?php echo num($x) ?>) = <?php echo $x == 0 ? '' : num( GD_Utils::radToPercent(atan((float)$y / (float)$x)) * 360 ) ?></em>
		</p>
		<?php endforeach; ?>
		
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

doPoints($x, $x);
doPoints(-$x, $x);
doPoints($x, -$x);
doPoints(-$x, -$x);

?>
	<hr />
<?php

$p = array('x' => array(), 'y' => array() );
//for( $i = 4; $i < 5; $i += 1 ) {
$i = 4;
	for( $a = 0; $a <= 1; $a += 0.1 ) {
		list($x1, $y1) = GD_Utils::cartesian($i, $a);
		$p['x'] []= $x1;
		$p['y'] []= $y1;
	}
//}
pbug($p);
doPoints($p['x'], $p['y']);

$center_x = 0;
$center_y = 0;
$width = 100;
$rotation = 0;

$star1 = GD_Utils::star5($center_x, $center_y, $width, $rotation = 0);

pbug($star1);