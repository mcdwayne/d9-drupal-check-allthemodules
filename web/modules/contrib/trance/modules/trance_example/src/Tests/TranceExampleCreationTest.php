<?php

namespace Drupal\trance_example\Tests;

use Drupal\trance\Tests\TranceCreationTest;

/**
 * Create a content entity and test saving it.
 *
 * @group trance_example
 */
class TranceExampleCreationTest extends TranceCreationTest {

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
