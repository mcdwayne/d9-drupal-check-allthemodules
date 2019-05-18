<?php

/**
 * @file
 * Contains \Drupal\probabilistic_weight\Plugin\field\widget\ProbabilisticWeightTextWidget.
 */

namespace Drupal\probabilistic_weight\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'probabilistic_weight_text' widget.
 *
 * @Plugin(
 *   id = "probabilistic_weight_text",
 *   module = "probabilistic_weight",
 *   label = @Translation("Text"),
 *   field_types = {
 *     "probabilistic_weight"
 *   }
 * )
 */
class ProbabilisticWeightTextWidget extends WidgetBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $element['weight'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]['weight']) ? $items[$delta]['weight'] : NULL,
      '#element_validate' => array('probabilistic_weight_validation'),
    );
    return $element;
  }

}
