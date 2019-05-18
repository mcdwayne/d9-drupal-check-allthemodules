<?php

namespace Drupal\phone_number\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'phone_number' widget.
 *
 * @FieldWidget(
 *   id = "phone_number_default",
 *   label = @Translation("Phone Number"),
 *   description = @Translation("Phone number field default widget."),
 *   field_types = {
 *     "phone_number",
 *     "telephone"
 *   }
 * )
 */
class PhoneNumberWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'default_country' => 'US',
      'placeholder' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $field_settings = $this->getFieldSettings();
    $allowed_countries = NULL;
    if (!empty($field_settings['allowed_countries'])) {
      $allowed_countries = $field_settings['allowed_countries'];
    }

    /** @var \Drupal\phone_number\Element\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');

    $form_state->set('field_item', $this);

    $element['default_country'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Country'),
      '#options' => $util->getCountryOptions($allowed_countries, TRUE),
      '#default_value' => $this->getSetting('default_country'),
      '#description' => $this->t('Default country for phone number input.'),
      '#required' => TRUE,
    ];

    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number Placeholder'),
      '#default_value' => $this->getSetting('placeholder') !== NULL ? $this->getSetting('placeholder') : 'Phone number',
      '#description' => $this->t('Number field placeholder.'),
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $result = [];

    $result[] = $this->t('Default country: @country', ['@country' => $this->getSetting('default_country')]);

    $result[] = $this->t('Number placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder') !== NULL ? $this->getSetting('placeholder') : 'Phone number']);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    /** @var ContentEntityInterface $entity */
    $entity = $items->getEntity();
    $settings = $this->getFieldSettings();
    $settings += $this->getSettings() + static::defaultSettings();

    $default_country = empty($settings['allowed_countries']) ?
      $settings['default_country'] :
      (empty($settings['allowed_countries'][$settings['default_country']]) ?
        key($settings['allowed_countries']) : $settings['default_country']);

    $element += [
      '#type' => 'phone_number',
      '#description' => $element['#description'],
      '#default_value' => [
        'value' => $item->value,
        'country' => !empty($item->country) ? $item->country : $default_country,
        'local_number' => $item->local_number,
        'extension' => $item->extension,
      ],
      '#phone_number' => [
        'allowed_countries' => !empty($settings['allowed_countries']) ? $settings['allowed_countries'] : NULL,
        'allowed_types' => !empty($settings['allowed_types']) ? $settings['allowed_types'] : NULL,
        'token_data' => !empty($entity) ? [$entity->getEntityTypeId() => $entity] : [],
        'placeholder' => isset($settings['placeholder']) ? $settings['placeholder'] : NULL,
        'extension_field' => isset($settings['extension_field']) ? $settings['extension_field'] : FALSE,
      ],
    ];

    return $element;
  }

}
