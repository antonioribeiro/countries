<?php

use PragmaRX\Countries\Update\Config as ServiceConfig;
use PragmaRX\Countries\Update\Helper;
use PragmaRX\Countries\Update\Updater;

require __DIR__.'/vendor/autoload.php';

ini_set('memory_limit', '4096M');

$config = new ServiceConfig();

$helper = new Helper($config);

$updater = new Updater($config, $helper);

$updater->update();
