<?php

function pbug() {
	$args = func_get_args();
	echo "\n<div style='border:1px dotted #AAA'>\n";
	foreach($args as $i => $arg) {
		echo "\n<pre>\n", print_r($arg, true), "\n</pre>\n";
	}
	echo "\n</div>\n";
}

