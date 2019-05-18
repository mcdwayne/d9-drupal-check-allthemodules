<?php

namespace Drupal\Tests\migrate_process_extras\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_process_extras\Plugin\migrate\process\EntityLookup;
use Drupal\node\Entity\Node;

/**
 * Test the entity lookup process plugin.
 *
 * @group migrate_process_extras
 */
class EntityLookupTest extends KernelTestBase {

  use ProcessMocksTrait {
    setUp as mockSetUp;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->mockSetUp();
    array_map([$this, 'installEntitySchema'], ['user', 'node']);


  }

  /**
   * Test the transformations.
   */
  public function testTransform() {
    $node1 = Node::create(['title' => $this->randomMachineName(), 'type' => 'page']);
    $node1->save();

    $configuration = [
      'entity_type_id' => 'node',
      'bundle' => 'page',
      'field_name' => 'title',
    ];
    $plugin = new EntityLookup($configuration, 'entity_lookup', []);
    $this->assertEquals($node1->id(), $plugin->transform($node1->getTitle(), $this->migrateExecutable, $this->row, 'destinationproperty'));

    $node2 = Node::create(['title' => $node1->getTitle(), 'type' => 'page']);
    $node2->save();

    // Look-up two nodes with the same value. We get false because two results
    // are not allowed by default.
    $this->assertEquals(FALSE, $plugin->transform($node1->getTitle(), $this->migrateExecutable, $this->row, 'destinationproperty'));

    $plugin = new EntityLookup($configuration + ['allow_multiple' => TRUE], 'entity_lookup', []);
    $this->assertEquals([$node1->id(), $node2->id()], $plugin->transform($node1->getTitle(), $this->migrateExecutable, $this->row, 'destinationproperty'));
  }

}
