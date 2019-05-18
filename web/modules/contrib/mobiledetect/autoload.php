<?php

/**
 * @file
 * autoload.php
 */

require_once __DIR__ . '/composer' . '/autoload_real.php';

return ComposerAutoloaderInitMobileDetect::getLoader();
