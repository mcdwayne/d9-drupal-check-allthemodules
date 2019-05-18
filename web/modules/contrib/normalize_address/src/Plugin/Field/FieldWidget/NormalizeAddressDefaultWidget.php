<?php

namespace Drupal\normalize_address\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'NormalizeAddressDefaultWidget' widget.
 *
 * @FieldWidget(
 *   id = "NormalizeAddressDefaultWidget",
 *   label = @Translation("Normalize Address"),
 *   field_types = {
 *     "normalize_address"
 *   }
 * )
 */
class NormalizeAddressDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $formState
  ) {

    $element['normalized_address_full'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Address'),
      '#default_value' => isset($items[$delta]->normalized_address_full) ? $items[$delta]->normalized_address_full : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter full Address of the Property (without unit number). Fields underneath will be filled automatically.'),
    ];

    $element['normalized_address_province'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Province'),
      '#default_value' => isset($items[$delta]->normalized_address_province) ? $items[$delta]->normalized_address_province : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter Province'),
    ];

    $element['normalized_address_city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#default_value' => isset($items[$delta]->normalized_address_city) ? $items[$delta]->normalized_address_city : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter City'),
    ];

    $element['normalized_address_street_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street address'),
      '#default_value' => isset($items[$delta]->normalized_address_street_address) ? $items[$delta]->normalized_address_street_address : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter Street address'),
    ];

    $element['normalized_address_building_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Building Number'),
      '#default_value' => isset($items[$delta]->normalized_address_building_number) ? $items[$delta]->normalized_address_building_number : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter Building Number'),
    ];

    $element['normalized_address_postal_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#default_value' => isset($items[$delta]->normalized_address_postal_code) ? $items[$delta]->normalized_address_postal_code : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter Postal Code'),
    ];

    $element['normalized_address_lattitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lattitude'),
      '#default_value' => isset($items[$delta]->normalized_address_lattitude) ? $items[$delta]->normalized_address_lattitude : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter Lattitude'),
    ];

    $element['normalized_address_longtitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Longtitude'),
      '#default_value' => isset($items[$delta]->normalized_address_longtitude) ? $items[$delta]->normalized_address_longtitude : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter Longtitude'),
    ];

    $element['normalized_address_unit_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Unit Number'),
      '#default_value' => isset($items[$delta]->normalized_address_unit_number) ? $items[$delta]->normalized_address_unit_number : NULL,
      '#empty_value' => '',
      '#description' => $this->t('Enter Unit Number'),
    ];


    $config = \Drupal::configFactory()->get('normalize_address.settings');

    if($config->get('normalize_address_api_key') && $config->get('normalize_address_country')) {
      $form['#attached']['drupalSettings']['normalize_address']['normalize_address']['api_key'] = $config->get('normalize_address_api_key');
      $form['#attached']['drupalSettings']['normalize_address']['normalize_address']['country_code'] = $config->get('normalize_address_country');
    }else{
      $form['#attached']['drupalSettings']['normalize_address']['normalize_address']['api_key'] = '';
      $form['#attached']['drupalSettings']['normalize_address']['normalize_address']['country_code'] = '';
    }

    $form['#attached']['library'][] = 'normalize_address/normalize_address';

    return $element;
  }

}
