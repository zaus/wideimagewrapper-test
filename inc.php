<?php

function pbug() {
	$args = func_get_args();
	foreach($args as $i => $arg) {
		echo "\n<pre>\n", print_r($arg, true), "\n</pre>\n";
	}
}

