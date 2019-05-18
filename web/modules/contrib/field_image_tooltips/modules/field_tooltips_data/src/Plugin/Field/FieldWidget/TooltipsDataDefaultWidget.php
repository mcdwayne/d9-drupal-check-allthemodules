<?php

namespace Drupal\field_tooltips_data\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'field_tooltips_data' widget.
 *
 * @FieldWidget(
 *   id = "field_tooltips_data_widget",
 *   label = @Translation("Tooltips"),
 *   field_types = {
 *     "field_tooltips_data"
 *   }
 * )
 */
class TooltipsDataDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = array(
      '#type' => 'hidden',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
    );
    $element['#attached']['library'][] = 'field_tooltips_data/tooltips-data-form';
    $element['#attached']['drupalSettings']['imageTooltip']['icon'] = '/' . drupal_get_path('module', 'field_tooltips_data') . '/images/druplicon-small.png';
    return $element;
  }

}
