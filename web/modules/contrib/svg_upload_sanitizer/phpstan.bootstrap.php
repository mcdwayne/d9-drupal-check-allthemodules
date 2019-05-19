<?php

/**
 * @file
 * Bootstrap file for PHPStan.
 */

require sprintf('%s/tests/bootstrap.php', __DIR__);

require sprintf('%s/svg_upload_sanitizer.module', __DIR__);

require sprintf('%s/vendor/drupal/core/includes/bootstrap.inc', __DIR__);
require sprintf('%s/vendor/drupal/core/modules/file/file.module', __DIR__);
