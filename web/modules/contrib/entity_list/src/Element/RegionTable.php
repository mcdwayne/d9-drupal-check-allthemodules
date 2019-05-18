<?php

namespace Drupal\entity_list\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Table;

/**
 * Class RegionTable.
 *
 * @package Drupal\entity_list\Element
 *
 * @see \Drupal\Core\Render\Element\Table.
 *
 * New property:
 *  - regions: an array of region keyed by the region name.
 *
 * Usage example:
 * @code
 * $form['table'] = [
 *   '#type' => 'region_table',
 *   '#regions' => [
 *     'main' => [
 *       'title' => 'Main',
 *       'message' => 'Optional empty message',
 *     ]
 *   ],
 *   '#region_groups' => '',
 *   ...
 * ];
 * @endcode
 *
 * @FormElement("region_table")
 */
class RegionTable extends Table {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#regions'] = ['' => []];
    $info['#region_group'] = '';
    $info['#theme'] = 'region_table';
    // Prepend FieldUiTable's prerender callbacks.
    array_unshift($info['#pre_render'],
      [get_class($this), 'tablePreRender'],
      [get_class($this), 'preRenderRegionRows']
    );
    return $info;
  }

  /**
   * Determine the colspan to use for region rows.
   *
   * @param array $elements
   *   The current form element.
   *
   * @return int
   *   An integer representing the colspan property to use for region rows.
   */
  public static function determineRegionColspan(array $elements) {
    $columns_count = 0;
    foreach ($elements['#header'] as $header) {
      $columns_count += (is_array($header) && isset($header['colspan']) ? $header['colspan'] : 1);
    }
    return $columns_count;
  }

  /**
   * Performs pre-render tasks on field_ui_table elements.
   *
   * @param array $elements
   *   A structured array containing two sub-levels of elements. Properties
   *   used:
   *   - #tabledrag: The value is a list of $options arrays that are passed to
   *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @return array
   *   The $element with prepared variables ready for field-ui-table.html.twig.
   *
   * @see drupal_render()
   * @see \Drupal\Core\Render\Element\Table::preRenderTable()
   */
  public static function tablePreRender(array $elements) {
    $rows_by_regions = [];
    $items = Element::children($elements);
    foreach ($items as $key) {
      $row = &$elements[$key];
      // Determine the region for the row.
      $region_name = call_user_func_array($row['#region_callback'], [&$row]);
      $rows_by_regions[$region_name][$key] = $key;
    }

    // Determine rendering order from the tree structure.
    foreach ($elements['#regions'] as $region_name => $region) {
      $elements['#regions'][$region_name]['rows_order'] = $rows_by_regions[$region_name] ?? [];
    }

    if (!empty($elements['#region_group'])) {
      $elements['#attached']['drupalSettings']['entity_list']['region_table']['region_group'] = $elements['#region_group'];
    }

    // If the custom #tabledrag is set and there is a HTML ID, add the table's
    // HTML ID to the options and attach the behavior.
    // @see \Drupal\Core\Render\Element\Table::preRenderTable()
    if (!empty($elements['#tabledrag']) && isset($elements['#attributes']['id'])) {
      foreach ($elements['#tabledrag'] as $options) {
        $options['table_id'] = $elements['#attributes']['id'];
        drupal_attach_tabledrag($elements, $options);
      }
    }

    return $elements;
  }

  /**
   * Performs pre-render to move #regions to rows.
   *
   * @param array $elements
   *   A structured array containing two sub-levels of elements. Properties
   *   used:
   *   - #tabledrag: The value is a list of $options arrays that are passed to
   *     drupal_attach_tabledrag(). The HTML ID of the table is added to each
   *     $options array.
   *
   * @return array
   *   The $element with prepared variables ready for field-ui-table.html.twig.
   */
  public static function preRenderRegionRows(array $elements) {
    $region_colspan = self::determineRegionColspan($elements);

    foreach ($elements['#regions'] as $region_name => $region) {
      $region_name_class = Html::getClass($region_name);
      $elements['#rows'][$region_name] = [
        'class' => [
          'region-title',
          "region-{$region_name_class}-message",
        ],
        'data-region' => $region_name,
        'no_striping' => TRUE,
        'data' => [
          ['data' => $region['title'], 'colspan' => $region_colspan],
        ],
      ];

      if (isset($region['message'])) {
        $class = (empty($region['rows_order'])) ? 'region-empty' : 'region-populated';
        $elements['#rows']["{$region_name}_message"] = [
          'class' => [
            'region-message',
            "region-{$region_name_class}-message",
            $class,
          ],
          'no_striping' => TRUE,
          'data' => [
            ['data' => $region['message'], 'colspan' => $region_colspan],
          ],
        ];
      }
      foreach ($region['rows_order'] as $key) {
        $element = $elements[$key];

        $row = ['data' => []];
        if (isset($element['#attributes'])) {
          $row += $element['#attributes'];
        }

        // Render children as table cells.
        foreach (Element::children($element) as $cell_key) {
          $child = $element[$cell_key];
          // Do not render a cell for children of #type 'value'.
          if (!(isset($child['#type']) && $child['#type'] == 'value')) {
            $cell = ['data' => $child];
            if (isset($child['#cell_attributes'])) {
              $cell += $child['#cell_attributes'];
            }
            $row['data'][] = $cell;
          }
        }
        $elements['#rows'][] = $row;

        unset($elements[$key]);
      }
    }
    return $elements;
  }

  /**
   * Helper function to build a row region.
   *
   * @param string $title
   *   The region label.
   * @param string $message
   *   The empty message.
   *
   * @return array
   *   An array representing the region, ready to be used with the region_table
   *   form element.
   */
  public static function buildRowRegion($title, $message) {
    return [
      'title' => $title,
      'message' => $message,
    ];
  }

  /**
   * Helper function to build region table row.
   *
   * @param string $label
   *   The row label.
   * @param array $regions
   *   An array of regions to use as select element.
   * @param callable $region_callback
   *   The region callback used to determine the region at the prerender time.
   * @param int $weight
   *   The default weight.
   * @param string $region
   *   The default region.
   * @param bool $draggable
   *   Determine if the row is draggable or not (default to TRUE).
   *
   * @return array
   *   An array representing a row.
   */
  public static function buildRow($label, array $regions, callable $region_callback, $weight, $region, $draggable = TRUE) {
    $row = [];
    $row['#attributes'] = [];
    $row['#weight'] = $weight;
    $row['#region_callback'] = $region_callback;
    if ($draggable) {
      $row['#attributes']['class'][] = 'draggable';
      $row['#attributes']['class'][] = 'tabledrag-leaf';
    }

    $row['label'] = [
      '#plain_text' => $label,
    ];
    $row['weight'] = [
      '#title' => t('Weight for @title', ['@title' => $label]),
      '#type' => 'weight',
      '#title_display' => 'invisible',
      '#default_value' => $weight,
      '#attributes' => [],
    ];
    $row['region'] = [
      '#title' => t('Region for @title', ['@title' => $label]),
      '#type' => 'select',
      '#title_display' => 'invisible',
      '#options' => $regions,
      '#default_value' => $region,
      '#attributes' => [],
    ];
    return $row;
  }

}
