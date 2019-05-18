<?php

/**
 * @file
 * Bootstrap file for PHPStan.
 */

require sprintf('%s/../phpunit/bootstrap.php', __DIR__);

require sprintf('%s/../../images_optimizer.module', __DIR__);

require sprintf('%s/../../vendor/drupal/core/includes/bootstrap.inc', __DIR__);
require sprintf('%s/../../vendor/drupal/core/modules/file/file.module', __DIR__);
require sprintf('%s/../../vendor/drupal/core/modules/filter/filter.module', __DIR__);
