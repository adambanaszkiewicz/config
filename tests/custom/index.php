<?php

function v()
{
    //call_user_func_array('var_dump', func_get_args());
}

include '../../vendor/autoload.php';

use Requtize\Config\Config;
use Requtize\Config\Loader\PhpLoader;
use Requtize\Config\Loader\IniLoader;
use Requtize\Config\Loader\YamlLoader;

$config1 = new Config();
$config1->appendFromLoader(new PhpLoader(realpath('../resources/full-same-php.php')));

$config2 = new Config();
$config2->appendFromLoader(new IniLoader(realpath('../resources/full-same-ini.ini')));

$config3 = new Config();
$config3->appendFromLoader(new YamlLoader(realpath('../resources/full-same-yaml.yaml')));

v('PHP', $config1->all(), 'INI', $config2->all(), 'YAML', $config3->all());

v('--', '--', '----------------------------------------------------', '--', '--', $config1->get('float'), $config1->get('string'), $config1->get('first.keyTwo'), $config1->get('first.innerOne.innerTwo.innerThree'), $config1->get('database.bkp2.pass'));
v('--', '--', '----------------------------------------------------', '--', '--', $config2->get('float'), $config2->get('string'), $config2->get('first.keyTwo'), $config2->get('first.innerOne.innerTwo.innerThree'), $config2->get('database.bkp2.pass'));
v('--', '--', '----------------------------------------------------', '--', '--', $config3->get('float'), $config3->get('string'), $config3->get('first.keyTwo'), $config3->get('first.innerOne.innerTwo.innerThree'), $config3->get('database.bkp2.pass'));

v('--', '--', '------------------------MERGED-CONFIGS----------------------------', '--', '--', $config1->merge($config2)->merge($config3)->all());

//$config1->setCacheFilepath(__DIR__.'/!cache-file')->saveToCache();

echo 'asd';
$configCached = new Config(__DIR__.'/!cache-file');

$configCached->appendFromLoader(new PhpLoader(realpath('../resources/full-same-php.php')));
$configCached->appendFromLoader(new IniLoader(realpath('../resources/full-same-ini.ini')));
$configCached->appendFromLoader(new YamlLoader(realpath('../resources/full-same-yaml.yaml')));

v('--', '--', '------------------------CACHED-CONFIGS----------------------------', '--', '--', $configCached->all());
$configCached->saveToCache();
