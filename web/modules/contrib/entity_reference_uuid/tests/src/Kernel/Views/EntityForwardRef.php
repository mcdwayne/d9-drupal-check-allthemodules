<?php

namespace Drupal\Tests\entity_reference_uuid\Kernel\Views;

use Drupal\views\Views;

/**
 * A test.
 *
 * @group entity_reference_uuid
 */
class EntityForwardRef extends UuidViewsKernelTestBase {

  /**
   * Views to be enabled.
   *
   * @var array
   */
  public static $testViews = [
    'entity_forward_reference',
  ];

  public function testView() {
    // This is a view of test_entity_two entities that relate to a
    // test_entity_one that is published.
    $view = Views::getView('entity_forward_reference');
    $this->executeView($view);
    // The fixtures created 4 entities of test_entity_two, and 3 reference
    // a test_nodetype_one.
    $this->assertCount(3, $view->result);
    $expected_uuids = [
      '208adf04-b0ea-4d8c-b744-e574ec97d1d2',
      '83109432-2657-4217-bb1a-9bed7ef78599',
      '4ccccf2e-805c-421e-b029-bfa79dc7b006',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
    // Publish another related test_entity_one.
    $this->entities['4ae62194-1fae-4c3d-b210-ba4b0ad71f7e']->setPublished();
    $this->entities['4ae62194-1fae-4c3d-b210-ba4b0ad71f7e']->save();
    $view = Views::getView('entity_forward_reference');
    $this->executeView($view);
    $this->assertCount(4, $view->result);
    $expected_uuids = [
      '208adf04-b0ea-4d8c-b744-e574ec97d1d2',
      '83109432-2657-4217-bb1a-9bed7ef78599',
      '4ccccf2e-805c-421e-b029-bfa79dc7b006',
      '16b64581-e212-4a1e-a0c7-c471bf914eea',
    ];
    foreach ($view->result as $index => $row) {
      $this->assertEquals($expected_uuids[$index], $row->_entity->uuid());
    }
  }
}
