<?php

namespace Drupal\Tests\entity_pilot_git\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests EntityOperations class.
 *
 * @group entity_pilot_git
 */
class EntityOperationsTest extends KernelTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_pilot_git',
    'node',
    'user',
    'text',
    'system',
  ];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('user');
    $this->installConfig(['entity_pilot_git']);
  }

  /**
   * Tests the checkForUpdates function.
   */
  public function testEntityOperationsCheckForUpdates() {
    $node_type_id = 'article';
    $node_type = NodeType::create([
      'type' => $node_type_id,
      'name' => 'Article',
    ]);
    $node_type->save();
    $time = REQUEST_TIME;
    $node = Node::create([
      'type' => $node_type_id,
      'title' => 'Some node',
      'status' => 1,
      'uid' => 1,
    ]);
    $node->save();

    /** @var \Drupal\entity_pilot_git\EntityOperationsInterface $entity_operations */
    $entity_operations = $this->container->get('entity_pilot_git.entity_operations');
    $this->assertTrue($entity_operations->checkForUpdates($time - 1));

    $node2 = Node::create([
      'type' => $node_type_id,
      'title' => 'Another node',
      'status' => 1,
      'uid' => 1,
      'changed' => $time + 1,
    ]);
    $node2->save();

    // New node means more updates.
    $this->assertTrue($entity_operations->checkForUpdates($time));
    $node2->changed = $time - 1;
    $node2->save();
    $this->assertFalse($entity_operations->checkForUpdates($time));

    // Check type is skipped correctly.
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config */
    $config = $this->container->get('config.factory');
    $config->getEditable('entity_pilot_git.settings')
      ->set('skip_entity_types', [
        'node' => 'node',
      ])->save();

    $this->assertFalse($entity_operations->checkForUpdates($time));
  }

}
