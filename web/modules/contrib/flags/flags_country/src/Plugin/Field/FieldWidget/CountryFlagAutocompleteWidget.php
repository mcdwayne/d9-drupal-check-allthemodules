<?php

namespace Drupal\flags_country\Plugin\Field\FieldWidget;

use Drupal\country\Plugin\Field\FieldWidget\CountryAutocompleteWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'country_flag_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "country_flag_autocomplete",
 *   label = @Translation("Country autocomplete with flag"),
 *   field_types = {},
 *   weight = 5
 * )
 */
class CountryFlagAutocompleteWidget extends CountryAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#autocomplete_route_name'] = 'flags_country.country_autocomplete';
    $element['value']['#attached'] = array('library' => array('flags/flags'));
    return $element;
  }

}
