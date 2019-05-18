<?php

namespace Drupal\Tests\preserve_changed\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\preserve_changed_test\Entity\EntityTestChanged;

/**
 * Tests the PreservedChangedItem field item class.
 *
 * @group preserve_changed
 *
 * @coversDefaultClass \Drupal\preserve_changed\PreservedChangedItem
 */
class PreservedChangedItemTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'preserve_changed',
    'preserve_changed_test',
    'user',
  ];

  /**
   * @covers ::preSave
   */
  public function testPreservedChangedItem() {
    $this->installEntitySchema('entity_test_changed');

    $entity = EntityTestChanged::create([
      'type' => 'page',
      'name' => $this->randomString(),
    ]);
    $entity->save();
    $changed = $entity->getChangedTime();

    // At least a field should change in order to refresh the 'changed' time.
    $entity->set('name', $this->randomString())->save();

    // Check that the 'changed' timestamp was refreshed.
    $this->assertGreaterThan($changed, $entity->getChangedTime());

    $changed = $entity->getChangedTime();

    // Preserve the 'changed' timestamp value for next save.
    $entity->changed->preserve = TRUE;

    $entity->set('name', $this->randomString())->save();

    $this->assertEquals($changed, $entity->getChangedTime());
  }

}
