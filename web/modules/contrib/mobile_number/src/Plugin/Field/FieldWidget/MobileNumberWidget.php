<?php

namespace Drupal\mobile_number\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mobile_number\Element\MobileNumber;
use Drupal\mobile_number\MobileNumberUtilInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'mobile_number' widget.
 *
 * @FieldWidget(
 *   id = "mobile_number_default",
 *   label = @Translation("Mobile Number"),
 *   description = @Translation("Mobile number field default widget."),
 *   field_types = {
 *     "mobile_number",
 *     "telephone"
 *   }
 * )
 */
class MobileNumberWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    /** @var \Drupal\mobile_number\Element\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');

    return parent::defaultSettings() + [
      'default_country' => 'US',
      'countries' => [],
      'placeholder' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $field_settings = $this->getFieldSettings();
    $field_country_validation = isset($field_settings['countries']);

    /** @var \Drupal\mobile_number\Element\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');

    /** @var ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    $form_state->set('field_item', $this);

    $verification_enabled = !empty($this->fieldDefinition) && ($this->fieldDefinition->getType() == 'mobile_number');

    $element['default_country'] = [
      '#type' => 'select',
      '#title' => t('Default Country'),
      '#options' => $util->getCountryOptions([], TRUE),
      '#default_value' => $this->getSetting('default_country'),
      '#description' => t('Default country for mobile number input.'),
      '#required' => TRUE,
      '#element_validate' => [[
        $this,
        'settingsFormValidate',
      ],
      ],
    ];

    if (!$field_country_validation) {
      $element['countries'] = [
        '#type' => 'select',
        '#title' => t('Allowed Countries'),
        '#options' => $util->getCountryOptions([], TRUE),
        '#default_value' => $this->getSetting('countries'),
        '#description' => t('Allowed counties for the mobile number. If none selected, then all are allowed.'),
        '#multiple' => TRUE,
        '#attached' => ['library' => ['mobile_number/element']],
      ];
    }

    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Number Placeholder'),
      '#default_value' => $this->getSetting('placeholder') !== NULL ? $this->getSetting('placeholder') : 'Phone number',
      '#description' => t('Number field placeholder.'),
      '#required' => FALSE,
    ];

    if ($verification_enabled) {
    }

    return $element;
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public function settingsFormValidate(array $element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    array_pop($parents);
    $settings = $this->getFieldSettings();
    $settings += NestedArray::getValue($form_state->getValues(), $parents);

    $default_country = $settings['default_country'];
    $allowed_countries = $settings['countries'];
    if (!empty($allowed_countries) && !in_array($default_country, $allowed_countries)) {
      $form_state->setError($element, t('Default country is not in one of the allowed countries.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    /** @var \Drupal\mobile_number\Element\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $field_settings = $this->getFieldSettings();
    $field_country_validation = isset($field_settings['countries']);
    $country_options = $util->getCountryOptions();
    $countries = $this->getSetting('countries');
    $countries = !$field_country_validation ? ($countries ? implode(', ', $countries) : $this->t('All')) : NULL;

    $result = [];

    $result[] = $this->t('Default country: @country', ['@country' => $this->getSetting('default_country')]);

    if ($countries) {
      $result[] = $this->t('Allowed countries: @countries', ['@countries' => $countries]);
    }

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
    /** @var \Drupal\mobile_number\Element\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $settings = $this->getFieldSettings();
    $settings += $this->getSettings() + static::defaultSettings();

    $tfa_field = $util->getTfaField();

    $default_country = empty($settings['countries']) ?
      $settings['default_country'] :
      (empty($settings['countries'][$settings['default_country']]) ?
        key($settings['countries']) : $settings['default_country']);

    $element += [
      '#type' => 'mobile_number',
      '#description' => $element['#description'],
      '#default_value' => [
        'value' => $item->value,
        'country' => !empty($item->country) ? $item->country : $default_country,
        'local_number' => $item->local_number,
        'verified' => $item->verified,
        'tfa' => $item->tfa,
      ],
      '#mobile_number' => [
        'allowed_countries' => array_combine($settings['countries'], $settings['countries']),
        'verify' => ($util->isSmsEnabled() && !empty($settings['verify'])) ? $settings['verify'] : MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_NONE,
        'message' => !empty($settings['message']) ? $settings['message'] : NULL,
        'tfa' => (
          $entity->getEntityTypeId() == 'user' &&
          $tfa_field == $items->getFieldDefinition()->getName() &&
          $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality() == 1
        ) ? TRUE : NULL,
        'token_data' => !empty($entity) ? [$entity->getEntityTypeId() => $entity] : [],
        'placeholder' => isset($settings['placeholder']) ? $settings['placeholder'] : NULL,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    /** @var \Drupal\mobile_number\Element\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $op = MobileNumber::getOp($element, $form_state);
    $mobile_number = MobileNumber::getMobileNumber($element);

    if ($op == 'mobile_number_send_verification' && $mobile_number && ($util->checkFlood($mobile_number) || $util->checkFlood($mobile_number, 'sms'))) {
      return FALSE;
    }

    return parent::errorElement($element, $error, $form, $form_state);
  }

}
