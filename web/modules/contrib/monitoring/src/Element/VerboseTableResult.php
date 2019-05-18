<?php

/**
 * @file
 * Contains \Drupal\monitoring\Element\VerboseTableResult.
 */

namespace Drupal\monitoring\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides the verbose output for a table result of a sensor plugin.
 *
 * Usage Example:
 * @code
 *  $output[] = array(
 *    '#type' => 'verbose_table_result',
 *    '#title' => t('Title of sensor results'),
 *    '#header' => $table_header,
 *    '#rows' => $table_rows,
 *    '#query' => $query_string,
 *    '#query_args' => $query_args,
 *  );
 * @endcode
 *
 * @FormElement("verbose_table_result")
 */
class VerboseTableResult extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#title' => 'Result',
      '#header' => [],
      '#rows' => [],
      '#empty' => 'There are no results for this sensor to display.',
      '#query' => '',
      '#query_args' => [],
      '#pre_render' => [
        [$class, 'preRenderVerboseTableResult'],
      ],
      '#description' => '',
    ];
  }

  /**
   * Prepares a #type 'verbose_table_result' render element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #header, #rows, #query, #arguments.
   *
   * @return array
   *   The $element with prepared variables.
   */
  public static function preRenderVerboseTableResult(array $element) {
    $id = $string = str_replace(" ", "_", strtolower($element['#title']));
    $element[$id] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
      '#attributes' => ['id' => $id],
      '#description' => $element['#description'],
    ];
    $element[$id]['table'] = [
      '#type' => 'table',
      '#header' => $element['#header'],
      '#rows' => $element['#rows'],
      '#empty' => t(':empty', [':empty' => $element['#empty']]),
    ];
    if (!empty($element['#query'])) {
      $element[$id]['query'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => t('Query'),
        '#attributes' => ['class' => ['monitoring-verbose-query']],
      ];
      $element[$id]['query']['query'] = [
        '#type' => 'item',
        '#markup' => '<pre>' . $element['#query'] . '</pre>',
      ];
      if (!empty($element['#query_args'])) {
        $element[$id]['query']['query_args'] = [
          '#type' => 'item',
          '#title' => t('Arguments'),
          '#markup' => '<pre>' . var_export($element['#query_args'], TRUE) . '</pre>',
        ];
      }
    }

    return $element;
  }

}
