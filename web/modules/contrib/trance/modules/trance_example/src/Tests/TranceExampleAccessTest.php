<?php

namespace Drupal\trance_example\Tests;

use Drupal\trance\Tests\TranceAccessTest;

/**
 * Tests basic trance_example access functionality.
 *
 * @group trance_example
 */
class TranceExampleAccessTest extends TranceAccessTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['trance_example'];

  /**
   * Entity type id.
   *
   * @var string
   */
  protected $entityTypeId = 'trance_example';

}
