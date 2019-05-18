<?php

namespace Drupal\Tests\entity_reference_integrity_enforce\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the predelete hook.
 *
 * @group entity_reference_integrity_enforce
 */
class EntityPredeleteHookTest extends KernelTestBase {

  /**
   * Modules that implement hook_entity_predelete().
   *
   * @var array
   */
  public static $modules = [
    'comment',
    'entity_reference_integrity_enforce',
  ];

  /**
   * Test the weight of entity_predelete implementations.
   */
  public function testHookWeight() {
    $implementations = \Drupal::moduleHandler()->getImplementations('entity_predelete');
    $this->assertEquals('entity_reference_integrity_enforce', array_shift($implementations));
  }

}
