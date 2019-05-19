<?php

namespace Drupal\stock_photo_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'stock_photo_field_textfield' widget.
 *
 * @FieldWidget(
 *   id = "stock_photo_field_textfield",
 *   label = @Translation("Stock Photo Textfield"),
 *   field_types = {
 *     "stock_photo_field"
 *   }
 * )
 */
class StockPhotoTextfield extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => 60,
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#allowed_providers' => $this->getFieldSetting('allowed_providers'),
    ];

    return $element;
  }

}
