<?php

namespace Drupal\Tests\reference_table_formatter\Unit;

use Drupal\reference_table_formatter\EntityToTableRenderer;

/**
 * Tests reference table formatter entity to table renderer.
 *
 * @group reference_table_formatter
 */
class EntityToTableRendererTest extends ReferenceTableFormatterUnitTestCase {

  /**
   * Test standard table rendering.
   *
   * @dataProvider tableRenderingDataProvider
   */
  public function testRendering($entity_manager, $settings, $expected_table) {
    $table_renderer = new EntityToTableRenderer($entity_manager, $this->getRenderer());
    $table = $table_renderer->getTable('node', 'bundle', [], $settings);
    $this->assertEquals($expected_table, $table);
  }

  /**
   * Data provider for testing the table builder.
   */
  public function tableRenderingDataProvider() {
    $test_data = [];

    $mock_t_shirts = $this->tshirtMockEntities();

    $incomplete_shirts = $this->tshirtMockEntities();
    unset($incomplete_shirts[0]['field_size']);

    $test_data['standard_table'] = [
      $this->getEntityManager($mock_t_shirts),
      [
        'show_entity_label' => TRUE,
        'view_mode' => 'teaser',
        'empty_cell_value' => '',
        'hide_header' => FALSE,
      ],
      [
        '#theme' => 'table',
        '#header' => [
          'title' => 'Title',
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        '#rows' => [
          ['Red Medium T', '$1.00', 'M', 'Red'],
          ['Green Large T', '$2.00', 'L', 'Green'],
        ],
      ],
    ];

    $test_data['no_entity_label'] = [
      $this->getEntityManager($mock_t_shirts),
      [
        'show_entity_label' => FALSE,
        'view_mode' => 'teaser',
        'empty_cell_value' => '',
        'hide_header' => FALSE,
      ],
      [
        '#theme' => 'table',
        '#header' => [
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        '#rows' => [
          ['$1.00', 'M', 'Red'],
          ['$2.00', 'L', 'Green'],
        ],
      ],
    ];

    $test_data['incomplete_rows'] = [
      $this->getEntityManager($incomplete_shirts),
      [
        'show_entity_label' => FALSE,
        'view_mode' => 'teaser',
        'empty_cell_value' => '',
        'hide_header' => FALSE,
      ],
      [
        '#theme' => 'table',
        '#header' => [
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        '#rows' => [
          ['$1.00', '', 'Red'],
          ['$2.00', 'L', 'Green'],
        ],
      ],
    ];

    $test_data['empty_cell'] = [
      $this->getEntityManager($incomplete_shirts),
      [
        'show_entity_label' => FALSE,
        'view_mode' => 'teaser',
        'empty_cell_value' => 'N/A',
        'hide_header' => FALSE,
      ],
      [
        '#theme' => 'table',
        '#header' => [
          'field_price' => 'Price',
          'field_size' => 'Size',
          'field_color' => 'Color',
        ],
        '#rows' => [
          ['$1.00', 'N/A', 'Red'],
          ['$2.00', 'L', 'Green'],
        ],
      ],
    ];

    return $test_data;
  }

}
