<?php

include('../wideimageext/WideImageWrapper.class.php');

/**
 * Debug - print output to screen
 * @return mixed one or more things to print
 */
function pbug() {
	$args = func_get_args();
	echo "\n<div style='border:1px dotted #AAA'>\n";
	foreach($args as $i => $arg) {
		echo "\n<pre>\n", print_r($arg, true), "\n</pre>\n";
	}
	echo "\n</div>\n";
}


/**
 * Debug logging class
 */
class Debug {
	private static $level = self::INFO;

	const WARN = 2;
	const INFO = 4;
	const ERR = 1;
	const DEBUG = 8;

	private static function hasFlag($val, $flag) {
		return ($val & $flag == $flag);
	}

	public static function getLevel($level) {
		$output = array();
		if( self::hasFlag($level, self::WARN) ) {
			$output []= 'warn';
		}
		if( self::hasFlag($level, self::INFO) ) {
			$output []= 'info';
		}
		if( self::hasFlag($level, self::ERR) ) {
			$output []= 'error';
		}
		if( self::hasFlag($level, self::DEBUG) ) {
			$output []= 'debug';
		}

		return implode(' | ', $output);
	}

	public static function setLevel($level) {
		self::$level = $level;
	}

	/**
	 * Write to log with given severity
	 * @param  int $severity use Debug constants to indicate severity -- will actually write depending on logging level
	 * @param  mixed $msgs     one or more messages to log
	 * @return void           n/a
	 */
	public static function log($severity, $msgs) {
		if( $severity > self::$level ) return; // bail

		$msgs = func_get_args();
		// pop expected params
		/*$severity = */ array_shift($msgs);

		$output = '';
		foreach($msgs as $msg) {
			$output .= print_r($msg, true) . "\n";
		}
		error_log(sprintf("[%s] %s: -------------\n%s", date("Y/m/d h:i:s"), self::getLevel($severity), $output), 3, "render-personalizer.log");
	}
}//---	class	Debug


/**
 * Like jQuery $.extend - merge 2 or more arrays recursively overwriting previous values each time
 * @param  array $base   initial array of values; all keys within here will be overwritten by subsequent parameters (if present)
 * @param  array|bool $extend the array to "extend" with and overwrite previous values; may be followed by more than one array; if bool provided, will use value to determine whether to check for default "allowOverwrite" flag
 * @return array         merged arrays
 */
function extend($base, $extend) {
	// allow any number of arrays to be merged
	$extends = func_get_args();
	array_shift($extends); // remove base from list of arrays

	// check if we should expect an extra param in each array marked "allowOverwrite"
	if( is_bool($extends[0]) ) {
		$checkForOverrides = array_shift($extends); // convert to bool?
	}
	else {
		$checkForOverrides = false;
	}

	// for each array argument
	foreach($extends as $i => $extend) {
		// loop through extending param
		foreach($extend as $k => &$v) {
			// should we check for override restriction? if presesnt and no, skip
			if( $checkForOverrides 						// do we care?
				&& isset($base[$k.'#allowOverwrite']) && ! $base[$k.'#allowOverwrite']	// are we supposed to allow/restrict overwrites?
				) {	// 
				continue;
			}
			// if we don't have a previous value, or if just provided a value, "overwrite"
			elseif( !isset( $base[$k] ) || !is_array($v) ) {
				$base[$k] = $v;
			}
			// if value is an array, must recurse
			elseif( is_array($v) ) {
				$base[$k] = extend($base[$k], $checkForOverrides, $v);
			}
		}
	}

	return $base;
}//--	fn	extend


/**
 * Return the full asset paths (src and mask) from config data
 * @param  array $config  configuration settings
 * @param  string $assetId identifier of asset file, from config list
 * @return array          asset_path => path to base image, mask_path => path to mask overlay
 */
function _getAssetFromConfig($config, $assetId) {
	if( empty($config) || !isset($config['assets']) || !isset($config['asset_path']) ) {
		throw new InvalidArgumentException("Missing required config properties: possibly assets or asset_path");
	}

	foreach($config['assets'] as &$asset) {
		if( $assetId == $asset['id'] ) {
			return array(
				'asset_path' => $config['asset_path'] . $asset['image']
				,
				'mask_path' => $config['asset_path'] . $asset['mask']
			);
		}
	}
	throw new InvalidArgumentException("Invalid asset id provided: $assetId, unable to determine asset path");
}

function _getResizedPath($finfo, $outputSize) {
	return sprintf('%s/%s_%d_%d.%s', $finfo['dirname'], $finfo['filename'], $outputSize['w'], $outputSize['h'], $finfo['extension']);
}
function _crushAndSave($file, $size, $newPath) {
	// determine quality value based on extension
	if( false !== strpos($file, '.png') ) {
		$quality = -1;
	}
	else {
		$quality = 93;
	}
	$image = WideImage::load($file)
		->resize($size['w'], $size['h'])
		->saveToFile($newPath, $quality);
	
	// dispose
	unset($image);
}

function _checkAndCache($cachePath, $originalPath, &$config) {
	if( ! file_exists($cachePath)) {
		Debug::log(Debug::INFO, 'Crushing image to cache output dimensions:', $originalPath, sprintf('%s x %s', $config['output_size']['w'], $config['output_size']['h']));
		
		_crushAndSave($originalPath, $config['output_size'], $cachePath);
		
		Debug::log(Debug::INFO, 'Images Cached');
	}
}
/**
 * Given a config bundle and an asset identifier, check if we've resized it before, if not do so; and return resized result
 * @param  array $config  config bundle
 * @param  string $assetId name of asset to lookup
 * @return array          asset_path => path to asset, mask_path => path to mask
 */
function getAssetFromResizedCache($config, $assetId) {
	// prep paths
	extract( _getAssetFromConfig($config, $assetId ) ); // asset, mask
	$assetSizePath = _getResizedPath(pathinfo($asset_path), $config['output_size']);
	$maskSizePath = _getResizedPath(pathinfo($mask_path), $config['output_size']);

	// have we already done this?
	_checkAndCache($assetSizePath, $asset_path, $config);
	_checkAndCache($maskSizePath, $mask_path, $config);

	return array(
		'asset_path' => $assetSizePath,
		'mask_path' => $maskSizePath
	);
}

/**
 * Map given coordinates from initial scale to expected coordinate plane
 * @param  float $scale original coordinate scale -- the max dimension of the original coordinate plane
 * @param  float $x     horizontal coord
 * @param  float $y     vertical coord
 * @param  float $fit_w map to this coordinate plane - horizontal
 * @param  float $fit_h map to this coordinate plane - vertical
 * @return array        x => float, y => float
 */
function rescale($scale, $x, $y, $fit_w, $fit_h) {
	return array(
		'x' => (float)$x / (float)$scale * (float)$fit_w
		, 'y' => (float)$y / (float)$scale * (float)$fit_h
	);
}

/**
 * Lazy isset check
 * @param  mixed $value   the value to check
 * @param  mixed $default if value not available, returns this
 * @return mixed          either the value if it exists or the default
 */
function v(&$value, $default) {
	if( !isset($value) ) return $default;
	return $value;
}