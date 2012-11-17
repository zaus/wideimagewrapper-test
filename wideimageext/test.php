x = 3, y = 4
<br />
<?php
include('../inc.php');



include('GD_Utils.class.php');

$x = 3;
$y = 4;

$r = GD_Utils::magnitude($x, $y);
$p = GD_Utils::polar($x, $y);

echo cos(3/5), ' ', sin(3/4), ' ', rad2deg(cos(3/4)), ' ', rad2deg(sin(3/5));

echo '<br />';
echo $r, '; ', $p[0], ' -- @ ', $p[1] , ' = ' , $p[1] * 360, ' deg';

$center_x = 0;
$center_y = 0;
$width = 100;
$rotation = 0;

$star1 = GD_Utils::star5($center_x, $center_y, $width, $rotation = 0);

pbug($star1);