<?php

namespace Drupal\flags_country\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\country\Plugin\Field\FieldWidget\CountryDefaultWidget;

/**
 * Plugin implementation of the 'country_select_menu' widget.
 *
 * @FieldWidget(
 *   id = "country_select_menu",
 *   label = @Translation("Country select options with flags"),
 *   field_types = {},
 *   weight = 5
 * )
 */
class CountrySelectMenuWidget extends CountryDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['value']['#type'] = 'select_icons';

    $mapper = \Drupal::service('flags.mapping.country');
    $element['value']['#options_attributes'] = $mapper->getOptionAttributes(
      array_keys($element['value']['#options'])
    );
    $element['value']['#attached'] = array('library' => array('flags/flags'));

    return $element;
  }

}
