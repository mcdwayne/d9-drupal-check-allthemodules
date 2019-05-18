<?php

namespace Drupal\Tests\entity_hierarchy\Kernel;

use Drupal\views\Tests\ViewResultAssertionTrait;
use Drupal\views\Views;

/**
 * Defines a class for testing views integration.
 *
 * @group entity_hierarchy
 */
class ViewsIntegrationTest extends EntityHierarchyKernelTestBase {

  use ViewResultAssertionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_hierarchy',
    'entity_test',
    'system',
    'user',
    'dbal',
    'field',
    'views',
    'entity_hierarchy_test_views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function additionalSetup() {
    parent::additionalSetup();
    $this->installConfig('entity_hierarchy_test_views');
    $this->installConfig('system');
    $this->installSchema('system', ['router', 'key_value_expire']);
  }

  /**
   * Tests views integration.
   */
  public function testViewsIntegrationDirectChildren() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_1', [$this->parent->id()]);
    $this->assertCount(3, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views integration.
   */
  public function testViewsIntegrationIncludingGrandChildren() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
      [
        'name' => 'Child 5',
        'id' => 9,
      ],
      [
        'name' => 'Child 4',
        'id' => 8,
      ],
      [
        'name' => 'Child 3',
        'id' => 7,
      ],
      [
        'name' => 'Child 2',
        'id' => 6,
      ],
      [
        'name' => 'Child 1',
        'id' => 5,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_2', [$this->parent->id()]);
    $this->assertCount(8, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views integration.
   */
  public function testViewsIntegrationParents() {
    $children = $this->createChildEntities($this->parent->id(), 1);
    $child = reset($children);
    $grandchildren = $this->createChildEntities($child->id(), 1);
    // Tree is as follows
    // 1     : Parent
    // - 2   : Child 1
    // - - 3 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Parent',
        'id' => 1,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_3', [reset($grandchildren)->id()]);
    $this->assertCount(2, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views sibling integration.
   */
  public function testViewsIntegrationSiblings() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing single hierarchy.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_4', [$child->id()]);
    $this->assertCount(2, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

  /**
   * Tests views sibling integration with show_self enabled.
   */
  public function testViewsIntegrationSiblingsShowSelf() {
    $children = $this->createChildEntities($this->parent->id(), 3);
    $child = reset($children);
    $this->createChildEntities($child->id(), 5);
    // Tree is as follows
    // 1     : Parent
    // - 4   : Child 3
    // - 3   : Child 2
    // - 2   : Child 1
    // - - 9 : Child 5
    // - - 8 : Child 4
    // - - 7 : Child 3
    // - - 6 : Child 2
    // - - 5 : Child 1
    // Test showing siblings with the show_self option enabled.
    $expected = [
      [
        'name' => 'Child 3',
        'id' => 4,
      ],
      [
        'name' => 'Child 2',
        'id' => 3,
      ],
      [
        'name' => 'Child 1',
        'id' => 2,
      ],
    ];
    $executable = Views::getView('entity_hierarchy_test_children_view');
    $executable->preview('block_5', [$child->id()]);
    $this->assertCount(3, $executable->result);
    $this->assertIdenticalResultset($executable, $expected, ['name' => 'name', 'id' => 'id']);
  }

}
