<?php

namespace Drupal\trance_example\Tests;

use Drupal\trance\Tests\TranceOwnerTest;

/**
 * Tests trance_example owner functionality.
 *
 * @group Entity
 */
class TranceExampleOwnerTest extends TranceOwnerTest {

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
