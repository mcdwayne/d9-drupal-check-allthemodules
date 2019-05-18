<?php

$autoloader = require __DIR__ . '/../vendor/autoload.php';

$autoloader->add('Drupal\\', 'vendor/drupal/core/lib');
$autoloader->addPsr4('Drupal\\Tests\\', 'vendor/drupal/core/tests/Drupal/Tests');
$autoloader->addPsr4('Drupal\\ds\\', 'vendor/drupal/ds/src');
$autoloader->addPsr4('Drupal\\contact_link\\', 'src');
