<?php

namespace Drupal\address_autocomplete_gmaps\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;

/**
 * Plugin implementation of the 'address_autocomplete_gmaps' widget.
 *
 * @FieldWidget(
 *   id = "address_autocomplete_gmaps",
 *   label = @Translation("Address autocomplete w Google Maps"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressAutocomplete extends AddressDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'api_key' => '',
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    unset($elements['default_country']);

    $elements['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#default_value' => $this->getSetting('api_key'),
      '#description' => t('Your Google API key.'),
    ];
    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of the location field.'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the location field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    unset($summary['default_country']);
    $summary['api_key'] = t('Google API key: @key', ['@key' => $this->getSetting('api_key')]);
    $summary['size'] = t('Size: @size', ['@size' => $this->getSetting('size')]);
    $summary['placeholder'] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder') ?: t('Not set.')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $class = isset($form['#attributes']['class']) ? $form['#attributes']['class'] : [];
    $class[] = 'address-autocomplete-wrapper';
    $form['#attributes']['class'] = $class;
    $size = $this->getSetting('size');
    $placeholder = $this->getSetting('placeholder');
    $maxlength = $this->getFieldSetting('max_length');

    $element['location_field'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#size' => $size,
      '#placeholder' => $placeholder,
      '#maxlength' => $maxlength,
      '#attributes' => [
        'class' => [
          'address-autocomplete-input',
          'address-autocomplete-component--hidden',
        ],
      ],
    ];

    $element += parent::formElement($items, $delta, $element, $form, $form_state);
    if (!($api_key = $this->getSetting('api_key'))) {
      $config = \Drupal::configFactory();
      $api_key = $config->get('address_autocomplete_gmaps.settings')->get('api_key');
    }
    // Get apiKey inside Drupal js:
    // var apiKey = settings.addressAutocomplete.apiKey;
    $element['#attached']['drupalSettings']['addressAutocomplete']['apiKey'] = $api_key;
    $element['#attached']['library'][] = 'address_autocomplete_gmaps/google_places_autocomplete';

    return $element;
  }

}
