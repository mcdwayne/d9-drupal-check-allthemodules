<?php

namespace Drupal\ordered_list\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an ordered list form element.
 *
 * Usage example:
 * @code
 * $form['list'] = [
 *   '#type' => 'ordered_list',
 *   '#title' => t('List'),
 *   '#title_display' => 'invisible',
 *   '#description' => t('Description.'),
 *   '#options' => [
 *     'item1' => t('Item 1'),
 *     'item2' => t('Item 2'),
 *     'item3' => t('Item 3'),
 *     'item4' => t('Item 4'),
 *   ],
 *   '#default_value' => ['item4', 'item2'],
 *   '#required' => TRUE,
 *   '#disabled' => FALSE,
 *   '#labels' => [
 *     'items_available' => t('Available'),
 *     'items_selected' => t('Selected'),
 *     'control_select' => t('Select'),
 *     'control_deselect' => t('Deselect'),
 *     'control_moveup' => t('Move Up'),
 *     'control_movedown' => t('Move Down'),
 *   ],
 * ];
 * @endcode
 *
 * @FormElement("ordered_list")
 */
class OrderedList extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#process' => [
        [self::class, 'process'],
      ],
      '#pre_render' => [
        [self::class, 'preRender'],
      ],
      '#theme' => 'ordered_list',
      '#theme_wrappers' => ['form_element'],
      '#options' => [],
      '#default_value' => [],
      '#labels' => [],
    ];
  }

  /**
   * Processes an ordered list form element.
   */
  public static function process(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#attached']['library'][] = 'ordered_list/ordered_list';
    $values = $element['#value'] ?? $element['#default_value'];
    $available = &$element['#items']['available'];
    $selected = &$element['#items']['selected'];
    $options = $element['#options'];
    $delta = 0;
    foreach ($options as $value => $label) {
      $available[$value] = [
        'label' => $label,
        'value' => $value,
        'delta' => $delta++,
      ];
    }
    foreach ($values as $value) {
      if (isset($options[$value])) {
        $selected[$value] = $available[$value];
        unset($available[$value]);
      }
    }
    $element['#labels'] = (array) $element['#labels'] + self::defaultLabels();

    $element['values'] = [
      '#type' => 'hidden',
      '#value' => implode(',', $values),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $values = [];
    if (FALSE === $input) {
      $element += ['#default_value' => []];
      foreach ($element['#default_value'] as $value) {
        $values[$value] = $value;
      }
    }
    elseif (!empty($input['values'])) {
      foreach (explode(',', $input['values']) as $value) {
        $values[$value] = $value;
      }
    }
    return $values;
  }

  /**
   * Prepares an ordered list form element.
   */
  public static function preRender($element) {
    static::setAttributes($element, ['ordered-list']);
    Element::setAttributes($element, ['id']);
    return $element;
  }

  /**
   * Returns default labels for the ordered list form element.
   *
   * @return array
   *   An array of default labels.
   */
  public static function defaultLabels() {
    return [
      'items_available' => t('Available'),
      'items_selected' => t('Selected'),
      'control_select' => t('Select'),
      'control_deselect' => t('Deselect'),
      'control_moveup' => t('Move Up'),
      'control_movedown' => t('Move Down'),
    ];
  }

}
