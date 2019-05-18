<?php

namespace Drupal\sms_phone_number\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\phone_number\Plugin\Field\FieldType\PhoneNumberItem;
use Drupal\sms_phone_number\SmsPhoneNumberUtilInterface;

/**
 * Plugin implementation of the 'sms_phone_number' field type.
 *
 * @FieldType(
 *   id = "sms_phone_number",
 *   label = @Translation("SMS Phone Number"),
 *   description = @Translation("Stores international number, local number, country code, verified status, and tfa option for sms_phone numbers."),
 *   default_widget = "sms_phone_number_default",
 *   default_formatter = "sms_phone_number_international",
 *   constraints = {
 *     "SmsPhoneNumber" = {}
 *   }
 * )
 */
class SmsPhoneNumberItem extends PhoneNumberItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    return parent::defaultFieldSettings() + [
      'verify' => $util->isSmsEnabled() ? $util::PHONE_NUMBER_VERIFY_OPTIONAL : SmsPhoneNumberUtilInterface::PHONE_NUMBER_VERIFY_NONE,
      'message' => $util::PHONE_NUMBER_DEFAULT_SMS_MESSAGE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['verified'] = [
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ];
    $schema['columns']['tfa'] = [
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['verified'] = DataDefinition::create('boolean')
      ->setLabel(t('Verified Status'));

    $properties['tfa'] = DataDefinition::create('boolean')
      ->setLabel(t('TFA Option'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $values = $this->getValue();

    $number = NULL;
    $country = NULL;

    if (!empty($values['country'])) {
      if (!empty($values['local_number'])) {
        $number = $values['local_number'];
      }
      $country = $values['country'];
    }

    if (!$number) {
      $number = $values['value'];
    }

    if ($sms_phone_number = $util->getPhoneNumber($number, $country)) {
      $this->value = $util->getCallableNumber($sms_phone_number);
      $this->country = $util->getCountry($sms_phone_number);
      $this->local_number = $util->getLocalNumber($sms_phone_number, TRUE);
      $this->tfa = !empty($values['tfa']) ? 1 : 0;
      if (isset($values['verified'])) {
        // This could be coming in via migrate or is being set programmatically.
        $this->verified = (bool) $values['verified'];
      }
      elseif ($this->verify() === TRUE) {
        $this->verified = TRUE;
      }
      else {
        $this->verified = FALSE;
      }
    }
    else {
      $this->value = NULL;
      $this->local_number = NULL;
    }

    parent::preSave();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    /** @var \Drupal\phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');

    $element = parent::storageSettingsForm($form, $form_state, $has_data);
    $element['unique']['#options'][$util::PHONE_NUMBER_UNIQUE_YES_VERIFIED] = t('Yes, only verified numbers');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $field = $this->getFieldDefinition();
    $settings = $this->getSettings() + $this->defaultFieldSettings();

    // @todo Remove FALSE after port of TFA for drupal 8 is available
    if ($form['#entity'] instanceof User && FALSE) {
      $element['tfa'] = [
        '#type' => 'checkbox',
        '#title' => t('Use this field for two-factor authentication'),
        '#description' => t("If enabled, users will be able to choose if to use the number for two factor authentication. Only one field can be set true for this value, verification must be enabled, and the field must be of cardinality 1. Users are required to verify their number when enabling their two-factor authenticaion. <a href='https://www.drupal.org/project/tfa' target='_blank'>Two Factor Authentication</a> must be installed, as well as a supported sms provider such as <a href='https://www.drupal.org/project/smsframework' target='_blank'>SMS Framework</a>."),
        '#default_value' => $this->tfaAllowed() && $util->getTfaField() === $this->getFieldDefinition()
          ->getName(),
        '#disabled' => !$this->tfaAllowed(),
      ];

      if ($this->tfaAllowed()) {
        $element['tfa']['#states'] = [
          'disabled' => ['input[name="settings[verify]"]' => ['value' => $util::PHONE_NUMBER_VERIFY_NONE]],
        ];
      }
    }

    $element['verify'] = [
      '#type' => 'radios',
      '#title' => t('Verification'),
      '#options' => [
        SmsPhoneNumberUtilInterface::PHONE_NUMBER_VERIFY_NONE => t('None'),
        SmsPhoneNumberUtilInterface::PHONE_NUMBER_VERIFY_OPTIONAL => t('Optional'),
        SmsPhoneNumberUtilInterface::PHONE_NUMBER_VERIFY_REQUIRED => t('Required'),
      ],
      '#default_value' => $settings['verify'],
      '#description' => (string) t('Verification requirement. Will send a verification code via SMS to the phone number when user requests to verify the number as their own. Requires <a href="https://www.drupal.org/project/smsframework" target="_blank">SMS Framework</a> or any other sms sending module that integrates with with the SMS Phone Number module.'),
      '#required' => TRUE,
      '#disabled' => !$util->isSmsEnabled(),
    ];

    $element['message'] = [
      '#type' => 'textarea',
      '#title' => t('Verification Message'),
      '#default_value' => $settings['message'],
      '#description' => t('The SMS message to send during verification. Replacement parameters are available for verification code (!code) and site name (!site_name). Additionally, tokens are available if the token module is enabled, but be aware that entity values will not be available on entity creation forms as the entity was not created yet.'),
      '#required' => TRUE,
      '#token_types' => [$field->getTargetEntityTypeId()],
      '#disabled' => !$util->isSmsEnabled(),
      '#element_validate' => [[
        $this,
        'fieldSettingsFormValidate',
      ],
      ],
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $element['message']['#element_validate'] = ['token_element_validate'];
      $element['message_token_tree']['token_tree'] = [
        '#theme' => 'token_tree',
        '#token_types' => [$field->getTargetEntityTypeId()],
        '#dialog' => TRUE,
      ];
    }

    return $element;
  }

  /**
   * Validate callback for SMS Phone Number field item.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $submit_handlers = $form_state->getSubmitHandlers();
    $submit_handlers[] = [
      $this,
      'fieldSettingsFormSubmit',
    ];
    $form_state->setSubmitHandlers($submit_handlers);
  }

  /**
   * Submit callback for sms_phone number field item.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function fieldSettingsFormSubmit(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $settings = $this->getSettings();
    if (!empty(['message'])) {
      t($settings['message']);
    }

    $tfa = !empty($this->getSetting('tfa'));
    $field_name = $this->getFieldDefinition()->getName();
    if (!empty($tfa)) {
      $util->setTfaField($field_name);
    }
    elseif ($field_name === $util->getTfaField()) {
      $util->setTfaField('');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $value = parent::generateSampleValue($field_definition);

    if (!empty($value)) {
      /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
      $util = \Drupal::service('sms_phone_number.util');

      $settings = [
        'verify' => $util->isSmsEnabled() ? $util::PHONE_NUMBER_VERIFY_OPTIONAL : $util::PHONE_NUMBER_VERIFY_NONE,
      ];

      switch ($settings['verify']) {
        case $util::PHONE_NUMBER_VERIFY_NONE:
          $value['verified'] = 0;
          break;

        case $util::PHONE_NUMBER_VERIFY_OPTIONAL:
          $value['verified'] = rand(0, 1);
          break;

        case $util::PHONE_NUMBER_VERIFY_REQUIRED:
          $value['verified'] = 1;
          break;
      }
    }

    return $value;
  }

  /**
   * Checks if tfa is allowed based on tfa module status and field cardinality.
   *
   * @return bool
   *   True or false.
   */
  public function tfaAllowed() {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    return $util->isTfaEnabled() && ($this->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality() == 1);
  }

  /**
   * Is the item's sms_phone number verified.
   *
   * Looks at the field's saved values or current session.
   *
   * @return bool
   *   TRUE if verified, else FALSE.
   */
  public function isVerified() {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $field_name = $this->getFieldDefinition()->getName();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');

    $sms_phone_number = $this->getPhoneNumber();

    if (!$sms_phone_number) {
      return FALSE;
    }

    $verified = (bool) \Drupal::entityQuery($entity_type_id)
      ->condition($id_key, (int) $entity->id())
      ->condition($field_name, $util->getCallableNumber($sms_phone_number))
      ->range(0, 1)
      ->condition("$field_name.verified", "1")
      ->count()
      ->execute();

    $verified = $verified || $util->isVerified($sms_phone_number);

    return $verified;
  }

  /**
   * Performs verification, assuming verification token and code were set.
   *
   * Adds to flood if failed. Will not attempt to verify if number is already
   * verified.
   *
   * @return bool|int|null
   *   TRUE if verification is successful, FALSE if wrong code provided, NULL if
   *   code or token not provided, and -1 if does not pass flood check.
   */
  public function verify() {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');
    $values = $this->getValue();
    $token = !empty($values['verification_token']) ? $values['verification_token'] : NULL;
    $code = !empty($values['verification_code']) ? $values['verification_code'] : NULL;

    if ($this->isVerified()) {
      return TRUE;
    }

    $sms_phone_number = $this->getPhoneNumber();

    if (!empty($token) && !empty($code) && $sms_phone_number) {
      if ($util->checkFlood($sms_phone_number)) {
        return $util->verifyCode($sms_phone_number, $code, $token);
      }
      else {
        return -1;
      }
    }
    else {
      return NULL;
    }
  }

  /**
   * Is sms_phone number unique within the entity/field.
   *
   * Will check against verified numbers only, if specificed.
   *
   * @param int $unique_type
   *   Unique type [PHONE_NUMBER_UNIQUE_YES|PHONE_NUMBER_UNIQUE_YES_VERIFIED].
   *
   * @return bool|null
   *   TRUE for is unique, FALSE otherwise. NULL if phone number is not valid.
   */
  public function isUnique($unique_type = SmsPhoneNumberUtilInterface::PHONE_NUMBER_UNIQUE_YES) {
    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');

    $entity = $this->getEntity();
    $field_name = $this->getFieldDefinition()->getName();

    if (!$sms_phone_number = $this->getPhoneNumber()) {
      return NULL;
    }
    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');
    $query = \Drupal::entityQuery($entity_type_id)
      // The id could be NULL, so we cast it to 0 in that case.
      ->condition($id_key, (int) $entity->id(), '<>')
      ->condition($field_name, $util->getCallableNumber($sms_phone_number))
      ->range(0, 1)
      ->count();

    if ($unique_type == SmsPhoneNumberUtilInterface::PHONE_NUMBER_UNIQUE_YES_VERIFIED) {
      $query->condition("$field_name.verified", "1");
      if ($this->isVerified()) {
        $result = !(bool) $query->execute();
      }
      else {
        $result = TRUE;
      }
    }
    else {
      $result = !(bool) $query->execute();
    }

    return $result;
  }

}
