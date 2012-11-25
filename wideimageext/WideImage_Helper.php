<?php

// libraries
if( !class_exists('WideImage') ) { include('../wideimage/lib/WideImage.php'); }
if( !class_exists('GD_Utils') ) { include('GD_Utils.class.php'); }


class WideImage_Helper {


	#region --------------- text and fonts -------------------
	#
	/**
	 * Set the currently active canvas font
	 * @param WideImage   $image the WideImage object
	 * @param string|object  $fontOrPath either the path to the font file, or an already-implemented WideImage font type
	 * @param int $fontSize   (default 12) the font size
	 * @param string  $hexColor   (default 000000) font color
	 * @param float $alpha   (default 0)
	 * @return the currently used Font object
	 */
	public static function font($image, $fontOrPath, $fontSize, $hexColor = '000000', $alpha = 0) {
		self::$lastFont = null;
		// are we setting up the font?
		if( is_string($fontOrPath) ) {
			self::$lastFont = $image->getCanvas()->useFont($fontOrPath, $fontSize, GD_Utils::rgba($hexColor, $alpha));
		}
		// or are we given an actual font
		elseif( $fontOrPath instanceof WideImage_Font_TTF
			|| $fontOrPath instanceof WideImage_Font_PS
			|| $fontOrPath instanceof WideImage_Font_GDF
			) {
			$image->getCanvas()->setFont($fontOrPath);
			self::$lastFont = $fontOrPath;
		}
		else {
			throw new WideImage_Exception('Must provide either font path or WideImage font');
		}

		return self::$lastFont;
	}//--	fn	setActiveFont

	/**
	 * Default settings for fonts
	 * @var array
	 */
	private static $_defaultFontOptions = array(
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
	 * Last used font
	 * @var WideImage_Font
	 */
	public static $lastFont;

	/**
	 * Write text at the specified coordinates, with given options
	 * @param  WideImage   $image the WideImage object
	 * @param  string $msg     the message to write
	 * @param  mixed $x       horizontal position, "smartish" coordinates
	 * @param  mixed $y       horizontal position, "smartish" coordinates
	 * @param  array $options list of optional options overriding $this->_defaultFontOptions; includes fontSize, fontPath, shadowOffset, hexColor, alpha, rotation
	 * @return WideImage_Canvas          canvas object for chaining
	 */
	public static function text($image, $msg, $x, $y, $options) {
		// merge options
		$options = array_merge(self::$_defaultFontOptions, $options);

		// do stuff
		
		// if given font, use it; otherwise use default
		if( false !== $options['font'] ) {
			self::$lastFont = $options['font'];
		}
		// if given path to font file, use it; otherwise default
		elseif( false !== $options['fontPath'] ) {
			self::$lastFont = self::font($image, $options['fontPath'], $options['fontSize'], $options['hexColor'], $options['alpha']);
		}
		// otherwise expect lastfont
		
		// add shadow first
		// only add shadow if expected
		if( isset($options['shadowOffset']) && false !== $options['shadowOffset'] ) {
			// draw text again with offset, in different color for shadow effect
			$originalColor = self::$lastFont->color;
			$font = self::$lastFont; // does this clone?
			$font->color = GD_Utils::rgba(
				isset($options['shadowHexColor']) ? $options['shadowHexColor'] : $options['hexColor']
				, isset( $options['shadowAlpha'] ) ? $options['shadowAlpha'] : ($options['alpha'] + 0.25)
				);

			// update the font
			$image->getCanvas()->setFont($font);

			// write the shadow text at offset
			// append in case we have "smart coordinates", adjusting for negative
			$image->getCanvas()->writeText(
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
			self::$lastFont->color = $originalColor;
			$image->getCanvas()->setFont(self::$lastFont);
		}//endif shadow

		// draw text where/how indicated
		$image->getCanvas()->writeText($x, $y, $msg, $options['rotation'] * 360);

		return $image->getCanvas();
	}//--	fn	text

	#endregion --------------- text and fonts -------------------



	/**
	 * Make the image transparent using position
	 * @param  WideImage   $image the WideImage object
	 * @param  integer $x horizontal
	 * @param  integer $y vertical
	 * @return WideImage_Image     chaining
	 */
	public static function makeTransparent($image, $x = 0, $y = 0) {
		$image->fill($x, $y, $image->getTransparentColor());
		return $image;
	}

	/**
	 * Convert given coordinates from external system to Image's coordinate plane; allows "smart" values like 'center'
	 * @param  WideImage   $image the WideImage object
	 * @param  int|mixed $x     smart coordinate - horizontal
	 * @param  int|mixed $y     smart coordinate - vertical
	 * @return array        new Point(x,y)
	 */
	public static function smart_point($image, $x, $y) {
		if(!is_numeric($x)) {
			$x = WideImage_Coordinate::fix($x, $image->getWidth()/*, $width*/);
		}

		if(!is_numeric($y)) {
			$y = WideImage_Coordinate::fix($y, $image->getHeight()/*, $height*/);
		}

		return array($x, $y);
	}


}