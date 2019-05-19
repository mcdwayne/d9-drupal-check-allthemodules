<?php

namespace Drupal\jquery_colorpicker\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\colorapi\Plugin\Field\FieldWidget\ColorapiWidgetBase;

/**
 * The default jQuery Colorpicker field widget.
 *
 * @FieldWidget(
 *   id = "jquery_colorpicker",
 *   label = @Translation("jQuery Colorpicker"),
 *   field_types = {
 *      "colorapi_color_field"
 *   }
 * )
 */
class JQueryColorpickerWidget extends ColorapiWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $summary['overview'] = $this->t('A jQuery Colorpicker color widget.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['color'] = [
      '#type' => 'jquery_colorpicker',
      '#default_value' => $items[$delta]->getHexadecimal() ? $items[$delta]->getHexadecimal() : '#FFFFFF',
      '#description' => $element['#description'],
      '#cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
    ];

    return $element;
  }

}
