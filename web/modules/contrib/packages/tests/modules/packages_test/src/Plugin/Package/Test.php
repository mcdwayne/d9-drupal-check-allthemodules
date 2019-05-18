<?php

namespace Drupal\packages_test\Plugin\Package;

use Drupal\packages\Plugin\PackageBase;

/**
 * A package used for testing.
 *
 * @Package(
 *   id = "test",
 *   label = @Translation("Test package"),
 *   description = @Translation("A package for testing."),
 *   enabled = TRUE,
 *   configurable = FALSE
 * )
 */
class Test extends PackageBase {

}
