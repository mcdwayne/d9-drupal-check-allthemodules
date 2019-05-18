<?php

/**
 * @file
 * Contains \Drupal\probabilistic_weight\Plugin\field\widget\ProbabilisticWeightSliderWidget.
 */

namespace Drupal\probabilistic_weight\Plugin\field\widget;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\field\Plugin\Type\Widget\WidgetBase;

/**
 * Plugin implementation of the 'probabilistic_weight_slider' widget.
 *
 * @Plugin(
 *   id = "probabilistic_weight_slider",
 *   module = "probabilistic_weight",
 *   label = @Translation("Slider"),
 *   field_types = {
 *     "probabilistic_weight"
 *   }
 * )
 */
class ProbabilisticWeightSliderWidget extends WidgetBase {

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   */
  public function formElement(array $items, $delta, array $element, $langcode, array &$form, array &$form_state) {
    $classes = array('prob_weight_field', 'prob_weight_slider');
    if ($element['#required']) {
      $classes[] = 'prob_weight_required';
    }
    $element['weight'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]['weight']) ? $items[$delta]['weight'] : NULL,
      '#element_validate' => array('probabilistic_weight_validation'),
      '#attributes' => array(
        'class' => $classes,
      ),
      '#attached' => array(
        'library' => array(
          array('system', 'jquery.ui.slider'),
        ),
        'js' => array(
          drupal_get_path('module', 'probabilistic_weight') . '/js/probabilistic_weight_slider.js' => array(
            'type' => 'file',
          ),
          array(
            'data' => array(
              'probabilistic_weight' => array(
                'empty_text' => t('Disable'),
                'disabled_text' => t('Disabled'),
              ),
            ),
            'type' => 'setting',
          ),
        ),
      ),
    );
    return $element;
  }

}
