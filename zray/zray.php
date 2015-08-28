<?php

namespace PhpMetrics;

$zre = new \ZRayExtension('phpmetrics', true);
$zre->setMetadata(array(
	'logo' => __DIR__ . DIRECTORY_SEPARATOR . 'logo.png',
));

// path SHOULD be absolute in zend server
// we cannot write following line:
// require_once __DIR__.'/vendor/autoload.php';
// COMPOSER AUTOLOAD DOESN'T WORK HERE
spl_autoload_register(function($class) {
	$classes = require '/usr/local/zend/var/plugins/phpmetrics/zray/vendor/composer/autoload_classmap.php';
	$filename = $classes[$class];
	// I don't know hyw Zend Server change path, but we should fix path here
	$filename = str_replace('phpmetrics/src', 'phpmetrics', $filename);
	require $filename;
});
require_once __DIR__.'/Collector.php';

// create arbitrary function executed in last
function shutdown() {
};
register_shutdown_function('PhpMetrics\shutdown');

// listen this function
$zre->traceFunction('PhpMetrics\shutdown', function(){}, array(new Collector, 'collect'));
