<?php

namespace Drupal\price\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'price_modified_default' widget.
 *
 * @FieldWidget(
 *   id = "price_modified_default",
 *   label = @Translation("Modified Price"),
 *   field_types = {
 *     "price_modified"
 *   }
 * )
 */
class PriceModifiedDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#type'] = 'price_modified';
    if (!$items[$delta]->isEmpty()) {
      $element['#default_value'] = $items[$delta]->toPrice()->toArray();
    }
    $element['#available_currencies'] = array_filter($this->getFieldSetting('available_currencies'));
    $element['#available_modifiers'] = array_filter($this->getFieldSetting('available_modifiers'));

    return $element;
  }

}
