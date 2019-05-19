<?php

namespace Drupal\trance_example\Tests;

use Drupal\trance\Tests\TranceTypeTest;

/**
 * Ensures that trance_example type functions work correctly.
 *
 * @group trance_example
 */
class TranceExampleTypeTest extends TranceTypeTest {

  /**
   * Entity type id.
   *
   * @var string
   */
  protected $entityTypeId = 'trance_example';

  /**
   * Bundle entity type id.
   *
   * @var string
   */
  protected $bundleEntityTypeId = 'trance_example_type';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['trance_example'];

}
