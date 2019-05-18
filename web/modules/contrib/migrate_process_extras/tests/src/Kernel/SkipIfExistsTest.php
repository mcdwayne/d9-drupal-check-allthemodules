<?php

namespace Drupal\Tests\migrate_process_extras\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_process_extras\Plugin\migrate\process\EntityLookup;
use Drupal\migrate_process_extras\Plugin\migrate\process\SkipIfExists;
use Drupal\node\Entity\Node;

/**
 * Test the skip if exists plugin.
 *
 * @group migrate_process_extras
 */
class SkipIfExistsTest extends KernelTestBase {

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
    $plugin = new SkipIfExists($configuration, 'skip_if_exists', []);
    $value = $this->randomMachineName();

    // Try with a non-existent node title.
    $this->assertEquals($plugin->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty'), $value);

    // Test an entity that exists is skipped.
    $this->setExpectedException(MigrateSkipRowException::class);
    $plugin->transform($node1->getTitle(), $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
