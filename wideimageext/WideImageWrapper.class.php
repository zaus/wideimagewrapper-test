<?php

if( !class_exists('WideImage') ) {
	include('../wideimage/lib/WideImage.php');
}

include('GD_Utils.class.php');

/**
 * Wrapper class with extra methods for WideImage_Image
 */
class WideImageWrapper {

	/**
	 * Image class used internally
	 * @var WideImage_Image
	 */
	public $image;

	/**
	 * Drawing canvas used internally
	 * @var WideImage_Canvas
	 */
	public $canvas;

	/**
	 * New helper wrapping the WideImage Image
	 * @param WideImage_Image|string $image the image or path
	 */
	public function __construct($image) {
		$this->setImage($image);
	}

	/**
	 * Magic fallthrough to WideImage
	 */
	function __call($method, $params)
	{

		try {
			return call_user_func_array( array(&$this->image, $method), $params);
		} catch( Exception $ex ) {
			throw new WideImage_InvalidCanvasMethodException("Function might not exist -- WideImage::{$method}:" . $ex->getMessage());
		}
	}


	/**
	 * Set the current image (and canvas)
	 * @param mixed $image either an existing WideImage or a path/data to load
	 */
	public function setImage($image) {
		if( is_string($image) ) {
			$this->image = WideImage::load($image);
		}
		elseif( is_null($image) || !$image ) {
			$this->image = false;
		}
		else {
			$this->image = &$image;
		}

		// only if we're not trying to set it externally
		if( false !== $this->image )
			// save it because we'll be using it a lot
			$this->canvas = &$this->image->getCanvas();
	}

	/**
	 * Cleanup
	 * @return void n/a
	 */
	public function dispose() {
		unset($this->canvas);
		unset($this->image);
		unset($this->font);
		unset($this);
	}

	/**
	 * Return a copy of the image wrapper
	 * @param  WideImageWrapper $wrapper the original wrapper
	 * @return WideImageWrapper          a copy of the wrapper
	 */
	public static function copy($wrapper) {
		$copy = new WideImageWrapper(false);
		$copy->setImage( $wrapper->image->copy() );
		return $copy;
	}

	#region --------------- text and fonts -------------------
	
	/**
	 * Standard font for text - set if using text methods
	 * @var WideImage_Font_TTF
	 */
	public $font;

	/**
	 * Set the currently active canvas font
	 * @param string|object  $fontOrPath either the path to the font file, or an already-implemented WideImage font type
	 * @param int $fontSize   (default 12) the font size
	 * @param string  $hexColor   (default 000000) font color
	 * @param float $alpha   (default 0)
	 * @return self
	 */
	public function setActiveFont($fontOrPath, $fontSize = 12, $hexColor = '000000', $alpha = 0) {
		// are we setting up the font?
		if( is_string($fontOrPath) ) {
			$this->font = $this->canvas->useFont($fontOrPath, $fontSize, GD_Utils::rgba($hexColor, $alpha));
		}
		// or are we given an actual font
		elseif( $fontOrPath instanceof WideImage_Font_TTF
			|| $fontOrPath instanceof WideImage_Font_PS
			|| $fontOrPath instanceof WideImage_Font_GDF
			) {
			$this->font = $font;
		}
		else {
			throw new WideImage_Exception('Must provide either font path or WideImage font');
		}

		return $this; // chain
	}//--	fn	setActiveFont

	/**
	 * Default settings for fonts
	 * @var array
	 */
	private $_defaultFontOptions = array(
			  'fontSize' => 12		// size of font
			, 'fontPath' => false	// path to font file, if not given, use actual font
			, 'font' => false			// if path not given, use this font object
			, 'shadowOffset' => false	// whether to not use a shadow (false), or the array of x/y offset of the shadow
			, 'hexColor' => '000000'	// default color
			, 'alpha' => 0			// default transparency
			, 'rotation' => 0
			// shadowHexColor
			// shadowAlpha
		);

	/**
	 * Write text at the specified coordinates, with given options
	 * @param  string $msg     the message to write
	 * @param  mixed $x       horizontal position, "smartish" coordinates
	 * @param  mixed $y       horizontal position, "smartish" coordinates
	 * @param  array $options list of optional options overriding $this->_defaultFontOptions; includes fontSize, fontPath, shadowOffset, hexColor, alpha, rotation
	 * @return WideImage_Canvas          canvas object for chaining
	 */
	public function text($msg, $x, $y, $options) {
		// merge options
		$options = array_merge($this->_defaultFontOptions, $options);

		// do stuff
		
		// if given font, use it; otherwise use default
		if( false !== $options['font'] ) {
			$this->font = $options['font'];
		}
		// if given path to font file, use it; otherwise default
		elseif( false !== $options['fontPath'] ) {
			$this->font = $this->canvas->useFont($options['fontPath'], $options['fontSize'], GD_Utils::rgba($options['hexColor'], $options['alpha']));
		}
		// otherwise expect lastfont

		// add shadow first
		// only add shadow if expected
		if( isset($options['shadowOffset']) && false !== $options['shadowOffset'] ) {
			// draw text again with offset, in different color for shadow effect
			$originalColor = $this->font->color;
			$font = $this->font; // does this clone?
			$font->color = GD_Utils::rgba(
				isset($options['shadowHexColor']) ? $options['shadowHexColor'] : $options['hexColor']
				, isset( $options['shadowAlpha'] ) ? $options['shadowAlpha'] : ($options['alpha'] + 0.25)
				);

			// update the font
			$this->canvas->setFont($font);

			// write the shadow text at offset
			// append in case we have "smart coordinates", adjusting for negative
			$this->canvas->writeText(
				sprintf('%s %s %s'
					, $x
					, $options['shadowOffset'][0] > 0 ? '+' : ''
					, $options['shadowOffset'][0]
					)
				, sprintf('%s %s %s'
					, $y
					, $options['shadowOffset'][1] > 0 ? '+' : ''
					, $options['shadowOffset'][1]
					)
				, $msg
				, $options['rotation'] * 360);

			// return to "original" font
			$this->font->color = $originalColor;
			$this->canvas->setFont($this->font);
		}//endif shadow

		// draw text where/how indicated
		$this->canvas->writeText($x, $y, $msg, $options['rotation'] * 360);

		// chain
		return $this;
	}
	#endregion --------------- text and fonts -------------------

	/**
	 * Save the file
	 * @param  string $filename the path
	 * @param mixed $params optional extra params - @see WideImage_Image::saveToFile
	 * @return void           n/a
	 */
	public function save($filename) {
		$args = func_get_args();
		switch( count($args) ) {
			case 1:
				return $this->image->saveToFile($args[0]);
				break;
			case 2:
				return $this->image->saveToFile($args[0], $args[1]);
				break;
			case 3:
				return $this->image->saveToFile($args[0], $args[1], $args[2]);
				break;
			default:
				return call_user_func_array(array(&$this->image, 'saveToFile'), $args);
				break;
		}

		//$this->image->saveToFile($filename);
	}


	#region ---------------- shapes -----------------------
	
	/**
	 * Make the image transparent using position
	 * @param  integer $x horizontal
	 * @param  integer $y vertical
	 * @return WideImage_Image     chaining
	 */
	public function makeTransparent($x = 0, $y = 0) {
		$this->image->fill($x,$y,$this->image->getTransparentColor());
		return $this->image;
	}

	private function smart_point($x, $y) {
		if(!is_numeric($x)) {
			$x = WideImage_Coordinate::fix($x, $this->image->getWidth()/*, $width*/);
		}

		if(!is_numeric($y)) {
			$y = WideImage_Coordinate::fix($y, $this->image->getHeight()/*, $height*/);
		}

		return array($x, $y);
	}


	function star_odd($num_points, $r, $x, $y, $angle, $color, $drawingMethod, $returnPoints = false) {
		list($x, $y) = $this->smart_point($x, $y);
		$points = GD_Utils::star($num_points, $x, $y, $r, $angle);
		$this->canvas->$drawingMethod( $points, $num_points, $color );
		return $returnPoints ? $points : $this;
	}
	function star_even($num_points, $r, $x, $y, $angle, $color, $drawingMethod, $returnPoints = false) {
		list($x, $y) = $this->smart_point($x, $y);
		// 2 identical polygons each with half the expected number of points, offset by half rotation
		$primary = GD_Utils::star($num_points/2, $x, $y, $r, $angle);
		$mirror = GD_Utils::star($num_points/2, $x, $y, $r, $angle+0.5);
		$this->canvas->$drawingMethod( $primary, $num_points/2, $color );
		$this->canvas->$drawingMethod( $mirror, $num_points/2, $color );

		// merge
		$points = array();
		$stop = count($primary);
		for($i = 1; $i < $stop; $i+=2) {
			$points []= $primary[$i-1];
			$points []= $mirror[$i-1];
			$points []= $primary[$i];
			$points []= $mirror[$i];

		}
		return $returnPoints ? $points : $this;
	}

	/**
	 * Draw a star
	 * @param  int  $num_points    the number of points
	 * @param  number  $r             radius (arm length)
	 * @param  number|smart  $x             center x coordinate
	 * @param  number|smart  $y             center y coordinate
	 * @param  string  $color         hex color code
	 * @param  decimal  $angle         rotation, in percent (0 - 1.0)
	 * @param  string  $drawingMethod regular GD image rendering method (filledpolygon or drawpolygon)
	 * @param  boolean $returnPoints  (default false) if true, return the list of points, otherwise chain
	 * @return mixed                 either the Wrapper (for chaining) or the list of points if $returnPoints = true
	 */
	public function star($num_points, $r, $x, $y, $color, $angle = 0, $drawingMethod = 'filledpolygon', $returnPoints = false) {
		// allow smart coords
		$r2 = $this->smart_point($r, 0);
		### pbug('smarter radius', $r2[0], $r, func_get_args());

		// fix parameters
		if( is_string($color) ) $color = GD_Utils::rgba($color, 0);

		// even and odd-shaped are different
		if( $num_points % 2 == 1 ) {
			return $this->star_odd($num_points, $r2[0], $x, $y, $angle, $color, $drawingMethod, $returnPoints);
		}
		else {
			return $this->star_even($num_points, $r2[0], $x, $y, $angle, $color, $drawingMethod, $returnPoints);
		}
	}

	/**
	 * Draw a star
	 * @param  int  $num_points    the number of points
	 * @param  number  $r             radius (arm length)
	 * @param  number|smart  $x             center x coordinate
	 * @param  number|smart  $y             center y coordinate
	 * @param  decimal  $angle         rotation, in percent (0 - 1.0)
	 * @return array                 the list of points
	 */
	public function star_points($num_points, $r, $x, $y, $angle = 0) {
		return $this->star($num_points, $r, $x, $y, '000000', $angle, 'filledpolygon', true);
	}


	/**
	 * Rectangle
	 * @param  number|smart  $w    dimensions
	 * @param  number|smart  $h   dimensions
	 * @param  number|smart  $x center coordinate
	 * @param  number|smart  $y center coordinate
	 * @param  decimal $angle angle of rotation in percent (of 360, 0 - 1.0)
	 * @param  string  $drawingMethod regular GD image rendering method (filledpolygon or drawpolygon)
	 * @param  boolean $returnPoints  (default false) if true, return the list of points, otherwise chain
	 * @return mixed                 either the Wrapper (for chaining) or the array of points if $returnPoints = true
	 */
	public function rect($w, $h, $x, $y, $color, $angle = 0, $drawingMethod = 'filledpolygon', $returnPoints = false) {
		// fix parameters
		if( is_string($color) ) $color = GD_Utils::rgba($color, 0);
		list($x, $y) = $this->smart_point($x, $y);
		list($w, $h) = $this->smart_point($w, $h); // necessary?  only for %, really

		$points = GD_Utils::rect($x, $y, $w, $h, $angle);

		pbug(__FUNCTION__, 'width', $w, 'height', $h, 'x', $x, 'y', $y, $points); ###
		$this->canvas->$drawingMethod( $points, 4, $color );
		return $returnPoints ? $points : $this;
	}
	
	public function square($w, $x, $y, $color, $angle = 0, $drawingMethod = 'filledpolygon', $returnPoints = false) {
		// fix parameters
		if( is_string($color) ) $color = GD_Utils::rgba($color, 0);
		list($x, $y) = $this->smart_point($x, $y);
		list($w, $h) = $this->smart_point($w, 0); // necessary?  only for %, really

		$points = GD_Utils::square($x, $y, $w, $angle);
		pbug(__FUNCTION__, 'width', $w, 'x', $x, 'y', $y, $points); ###
		$this->canvas->$drawingMethod( $points, 4, $color );
		return $returnPoints ? $points : $this;
	}

	#endregion ---------------- shapes -----------------------
	

}//---	class	WideImageWrapper