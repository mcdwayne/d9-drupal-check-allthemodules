<?php

declare(strict_types = 1);

namespace Drupal\Tests\field_autovalue\src\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Kernel test for the Field Autovalue plugin system.
 */
class FieldAutovalueKernelTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field_autovalue',
    'field_autovalue_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['field_autovalue_test']);
    $this->installSchema('node', ['node_access']);
  }

  /**
   * Tests that the test module plugin generates an automatic value.
   */
  public function testAutovalue(): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->container->get('entity_type.manager')->getStorage('node')->create([
      'type' => 'field_autovalue_test',
      'title' => 'My node',
    ]);

    $node->set('field_condition_1', TRUE);
    $node->save();
    $this->assertEquals('Condition 1 met', $node->get('field_auto_generated')->value);

    $node->set('field_condition_2', TRUE);
    $node->save();
    $this->assertEquals('Condition 1 met. Condition 2 met.', $node->get('field_auto_generated')->value);

    $node->save();
    // After a new save, there is no more transition from unchecked to checked
    // so the previous value gets set back.
    $this->assertEquals('Condition 1 met', $node->get('field_auto_generated')->value);
  }

}
