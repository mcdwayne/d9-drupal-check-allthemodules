<?php

namespace Drupal\address_usps\Plugin\Field\FieldWidget;

use Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget;
use Drupal\address_usps\AddressUSPSHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'address_usps' widget.
 *
 * @FieldWidget(
 *   id = "address_usps",
 *   label = @Translation("Address (USPS validation)"),
 *   field_types = {
 *     "address"
 *   }
 * )
 */
class AddressUSPSWidget extends AddressDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $default_settings = parent::defaultSettings();
    $default_settings['default_country'] = 'US';
    $default_settings['popup_validation'] = TRUE;

    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['message'] = [
      '#weight'       => 0,
      '#theme'        => 'status_messages',
      '#message_list' => [
        'warning' => [
          AddressUSPSHelper::US_ADDRESS_LIMIT_WIDGET_MESSAGE,
        ],
      ],
    ];

    $field_countries = $this->getFieldSetting('available_countries');

    if (!empty($field_countries && !isset($field_countries['US']))) {
      $form['message']['#message_list']['error'][] = AddressUSPSHelper::US_ADDRESS_US_COUNTRY_NOT_SELECTED;
    }

    $form['default_country']['#weight'] = 10;
    $form['default_country']['#default_value'] = $this->getSetting('default_country');

    $form['popup_validation'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use popup validation'),
      '#description'   => $this->t('If selected - validation popup with confirmation will be used before field value replacing.'),
      '#default_value' => $this->getSetting('popup_validation'),
      '#weight'        => 20,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $popup_validation = $this->getSetting('popup_validation');
    $popup_validation = $popup_validation ? $this->t('Yes') : $this->t('No');

    $summary['popup_validation'] = $this->t('Popup validation enabled: @popup_validation', ['@popup_validation' => $popup_validation]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Change element type for using Drupal\address_usps\Element\AddressUSPS.
    $element['address']['#type'] = 'address_usps';
    $element['address']['#popup_validation'] = $this->getSetting('popup_validation');

    return $element;
  }

}
