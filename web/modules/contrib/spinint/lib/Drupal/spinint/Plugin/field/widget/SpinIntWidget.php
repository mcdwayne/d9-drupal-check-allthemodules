<?php
/**
 * @file
 * Definition of Drupal\spinint\Plugin\field\widget\SpinIntWidget.
 */

namespace Drupal\spinint\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of spinint widget.
 * 
 * @Plugin(
 *   id = "spinint",
 *   module = "spinint",
 *   label = @Translation("Spinning integer"),
 *   field_types = {
 *     "number_integer"
 *   },
 *   settings = {
 *     "min" = "1"
 *   }
 * )
 */
class SpinIntWidget extends WidgetBase {
  
  /**
   * Implements Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $value = 1;
    $markup = theme('spinint', array('instance' => $this->instance, 'value' => $value));
    $el = array();
    $el = $element + array(
      '#type' => 'hidden',
      '#prefix' => $markup,
      '#default_value' => $value,
      '#attached' => array(
        'js' => array(
          drupal_get_path('module', 'spinint') . '/js/spinint.js',
        ),
        'css' => array(
          drupal_get_path('module', 'spinint') . '/css/spinint.css',
        ),
      ),
    );

    $el['#attached']['js'][] = array(
      'data' => array('spinint' => $this->instance['settings']),
      'type' => 'setting',
    );
    $element['value'] = $el;
  
    drupal_alter('spinint', $element, $this->instance, $field);
    return $element;
  }
  
}
