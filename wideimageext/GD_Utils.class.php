<?php

class GD_Utils {

	/**
	 * Calculate color from a hex value with alpha transparency without using image library.
	 * @see http://us2.php.net/manual/en/function.imagecolorallocatealpha.php#79486
	 * @param  string $hexColor regular hex notation like 'FF6600'
	 * @param  float $alpha    transparency as percent: 0 to 1.0; mapped to 0 (opaque) to 127 (transparent)
	 * @return [type]           [description]
	 */
	public static function rgba($hexColor,$alpha = 0) {
		return bindec(decbin( (float)$alpha*127 ) . decbin(hexdec($hexColor)));
	}


	#region ------------ shapes --------------------

	/**
	 * Calculate the vertices of a radial polygon with the given number of points
	 * @param  int  $num_points    number of outer vertices
	 * @param  number  $radius        distance from center to vertex
	 * @param  percent $initial_angle (default 0) how far to rotate shape initially (100% of 360 degrees)
	 * @param  number $center_x      (default 0) horizontal coordinate
	 * @param  number $center_y      (default 0) vertical coordinate
	 * @param  decimal $angle_step_factor  (default 1) factor by which to adjust the angle step calculations (i.e. for star to skip every-other-vertex use 2)
	 * @return array                 an array of points (array from @see cartesian)
	 */
	public static function radial_polygon($num_points, $radius, $initial_angle = 0, $center_x = 0, $center_y = 0, $angle_step_factor=1) {
		$points = array();
		// initial rotation offset to make up for disparity between visual and calculated coordinate plain
		$initial_angle -= 0.25;
		for($i = 0; $i < $num_points; $i++) {
			$point = self::cartesian($radius, $initial_angle + $angle_step_factor * (float)$i/(float)$num_points); // array( 'x'=> $center_x + $radius * cos( $initial_angle + $i/$num_points ) )
			// apply center offset
			if($center_x != 0) $point[0] += $center_x;
			if($center_y != 0) $point[1] += $center_y;

			// add flattened
			$points []= $point[0];
			$points []= $point[1];
		}

		return $points; // flatten?
	}

	/**
	 * Square
	 * @param  number  $center_x center coordinate
	 * @param  number  $center_y center coordinate
	 * @param  number  $width    dimensions
	 * @param  decimal $rotation angle of rotation in percent (of 360, 0 - 1.0)
	 * @return array            list of points of vertices
	 */
	public static function square($center_x, $center_y, $width, $rotation = 0) {
		return self::radial_polygon(4, $width/2, $rotation + 0.125, $center_x, $center_y);
	}

	/**
	 * Rectangle
	 * @param  number  $center_x center coordinate
	 * @param  number  $center_y center coordinate
	 * @param  number  $width    dimensions
	 * @param  number  $height   dimensions
	 * @param  decimal $rotation angle of rotation in percent (of 360, 0 - 1.0)
	 * @return array            list of points of vertices
	 */
	public static function rect($center_x, $center_y, $width, $height, $rotation = 0) {
		// translate to radial using given center as origin
		$points = array(
			self::polar(-$width/2, -$height/2),
			self::polar( $width/2, -$height/2),
			self::polar( $width/2,  $height/2),
			self::polar(-$width/2,  $height/2),
		);

		pbug(__CLASS__ . '::' . __FUNCTION__, 'radial points', $points, 'x', $center_x, 'y', $center_y, 'w', $width, 'h', $height);

		// translate to cartesian, adding initial rotation and offsets; flatten
		$flattened = array();
		foreach($points as $i => &$point) {
			$point = self::cartesian($point[0], $point[1] + $rotation);
	
			$flattened []= ($point[0] + $center_x);
			$flattened []= ($point[1] + $center_y);
		}

		return $flattened;
	}//--	fn	rect

	public static function diamond($center_x, $center_y, $width, $height, $rotation = 0) {
		// different bisection lengths
	}

	public static function star($num_points, $center_x, $center_y, $width, $rotation = 0) {
		// star composed of 2 radial polygons -- arms and "the point where the arms connect"
		// where inner is half-step rotated from outer, and some fraction of its radius
		return self::radial_polygon($num_points, $width/2, $rotation, $center_x, $center_y, 2);
	}

	public static function star5($center_x, $center_y, $width, $rotation = 0) {
		return self::star(5, $center_x, $center_y, $width/2, $rotation);
	}

	/* don't use -- instead make 2 triangles */
	public static function star6($center_x, $center_y, $width, $rotation = 0) {
		$first = self::radial_polygon(3, $width/2, $rotation, $center_x, $center_y);
		$second = self::radial_polygon(3, $width/2, $rotation+0.5, $center_x, $center_y);
		return array_merge($first, $second);
	}

	public static function heart($center_x, $center_y, $width, $height, $rotation = 0) {

	}

	#endregion ------------ shapes --------------------


	public static function magnitude($x, $y) {
		return sqrt( pow($x, 2) + pow($y, 2) );
	}

	/**
	 * Turn cartesian into polar
	 * @param  number $x horizontal
	 * @param  number $y vertical
	 * @return array    radius,angle (in % of 360, 0-1.0)
	 */
	public static function polar($x, $y) {
		// http://www.engineeringtoolbox.com/converting-cartesian-polar-coordinates-d_1347.html
		
		// radius
		$r = self::magnitude($x, $y);

		// angle, in % of circle
		$a = self::radToPercent(atan( (float)$y / (float)$x ));

		// adjust?
		if( $y < 0 ) {
			if( $x < 0 ) {
				$a = 0.5 + $a; // angle is positive
			}
			else {
				$a = 0.25 - $a; // angle is negative, so subtract to add
			}
		}
		elseif( $x < 0 ) {
			$a = 0.5 + $a; // angle is negative, so subtract to add
		}

		return array($r, $a);
	}



	public static function radToPercent($rad) {
		return $rad / (2*pi());
	}
	public static function percentToRad($percent) {
		return $percent * (2 * pi() );
	}

	/**
	 * Turn polar coordinates into cartesian
	 * @param  number $r radius
	 * @param  number $a angle (in percent of 360, 0-1.0)
	 * @return array    x,y
	 */
	public static function cartesian($r, $a) {
		$rad = self::percentToRad($a);
		## pbug( $r, $a, $rad, 'x', cos($rad), 'y', sin($rad), '----' );
		return array( $r * cos($rad), $r * sin($rad) );
	}


}//---	class	Wi_Coords aka WideImage_Coords