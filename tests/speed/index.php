<?php

include '../../vendor/autoload.php';

use Requtize\Config\Config;
use Requtize\Config\Loader\PhpLoader;
use Requtize\Config\Loader\IniLoader;
use Requtize\Config\Loader\YamlLoader;

if(is_file(__DIR__.'/!cache-file'))
    unlink(__DIR__.'/!cache-file');

$times = [];

$start = microtime(true);

$config = new Config(__DIR__.'/!cache-file');

$config->import(realpath('../resources/full-same-php.php'));
$config->import(realpath('../resources/full-same-ini.ini'));
$config->import(realpath('../resources/full-same-yaml.yaml'));

$times['import-6-files'] = number_format(microtime(true) - $start, 4);




$start = microtime(true);
$config->saveToCache();
$times['save-to-cache'] = number_format(microtime(true) - $start, 4);



$start = microtime(true);

$config = new Config(__DIR__.'/!cache-file');

$config->import(realpath('../resources/full-same-php.php'));
$config->import(realpath('../resources/full-same-ini.ini'));
$config->import(realpath('../resources/full-same-yaml.yaml'));

$times['get-from-cache'] = number_format(microtime(true) - $start, 4);




touch(realpath('../resources/full-same-yaml.yaml'));

$start = microtime(true);

$config = new Config(__DIR__.'/!cache-file');

$config->import(realpath('../resources/full-same-php.php'));
$config->import(realpath('../resources/full-same-ini.ini'));
$config->import(realpath('../resources/full-same-yaml.yaml'));

$times['get-from-cache-with-1-file-modified'] = number_format(microtime(true) - $start, 4);



var_dump($times);
