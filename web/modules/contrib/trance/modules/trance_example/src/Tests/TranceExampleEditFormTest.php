<?php

namespace Drupal\trance_example\Tests;

use Drupal\trance\Tests\TranceEditFormTest;

/**
 * Create a content entity and test edit functionality.
 *
 * @group trance_example
 */
class TranceExampleEditFormTest extends TranceEditFormTest {

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
