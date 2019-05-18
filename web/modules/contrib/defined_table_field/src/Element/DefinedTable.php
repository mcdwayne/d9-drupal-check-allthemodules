<?php

namespace Drupal\defined_table\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\defined_table\Plugin\Field\DefinedTableSourceSelectionTrait;

/**
 * Provides a form element for defined table.
 *
 * Table header and first row are predefined.
 *
 * @FormElement("defined_table")
 */
class DefinedTable extends FormElement {

  use DefinedTableSourceSelectionTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#title' => '',
      '#arguments_title' => '',
      '#header' => [],
      '#arguments' => [],
      '#input_type' => 'textfield',
      '#process' => [
        [$class, 'processTable'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Process callback.
   */
  public static function processTable(&$element, FormStateInterface $form_state, &$complete_form) {
    $values = is_array($element['#value']) ? $element['#value'] : [];

    // Check if the input_type is one of the allowed types.
    $input_type = in_array($element['#input_type'], [
      'textarea',
      'textfield',
      'checkbox',
    ]) ? $element['#input_type'] : 'textfield';

    $table = [
      '#type' => 'table',
      '#header' => [],
      '#tree' => TRUE,
    ];
    $element['table'] = &$table;

    // Prepare header.
    $table['#header'][] = $element['#arguments_title'];
    foreach ($element['#header'] as $header_cell) {
      $table['#header'][] = $header_cell;
    }

    $nrows = count($element['#arguments']);

    foreach ($element['#arguments'] as $i => $arg) {
      $table[$i][0] = [
        '#type' => 'item',
        '#markup' => $arg,
      ];

      foreach ($element['#header'] as $j => $arg) {
        $table[$i][$j] = [
          '#type' => $input_type,
          '#maxlength' => 2048,
          '#size' => 0,
          '#attributes' => [
            'style' => 'width:100%',
          ],
          '#default_value' => isset($values[$i][$j]) ? $values[$i][$j] : '',
        ];
      }
    }

    return $element;
  }

}
