<?php

namespace Drupal\Tests\flexiform\Kernel;

use Drupal\Tests\field_ui\Kernel\EntityFormDisplayTest;

/**
 * Tests the entity display configuration entities with Flexiform enabled.
 *
 * @group flexiform
 */
class FlexiformEntityFormDisplayTest extends EntityFormDisplayTest {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  public static $modules = [
    'field_ui',
    'field',
    'flexiform',
    'entity_test',
    'field_test',
    'user',
    'text',
  ];

}
