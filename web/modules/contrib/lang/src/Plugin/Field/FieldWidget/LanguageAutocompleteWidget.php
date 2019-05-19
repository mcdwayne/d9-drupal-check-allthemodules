<?php

/**
 * @file
 * Definition of Drupal\lang\Plugin\FieldWidget\LanguageAutocompleteWidget.
 */

namespace Drupal\lang\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;

/**
 * Plugin implementation of the 'language_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "language_autocomplete",
 *   label = @Translation("Language autocomplete widget"),
 *   field_types = {
 *     "lang"
 *   }
 * )
 */
class LanguageAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => '60',
      'autocomplete_route_name' => 'lang.autocomplete',
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $languages = getLanguageOptions();
    $element['value'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => (isset($items[$delta]->value) && isset($languages[$items[$delta]->value])) ? $languages[$items[$delta]->value] : '',
      '#autocomplete_route_name' => $this->getSetting('autocomplete_route_name'),
      '#autocomplete_route_parameters' => array(),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => 255,
      '#element_validate' => array('lang_autocomplete_validate'),
    );

    return $element;
  }
}
