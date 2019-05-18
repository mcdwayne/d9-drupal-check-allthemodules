<?php

namespace Drupal\janrain_connect_ui\Helpers;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * JanrainConnectUiFormHelper class.
 */
class JanrainConnectUiFormHelper {

  /**
   * Set error messages in form using the service result.
   *
   * @param array $serviceResult
   *   Janrain service result.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form State.
   * @param string $formId
   *   Form id.
   */
  public static function setServiceResultErrorsAsDrupalFormErrors(
    array $serviceResult,
    FormStateInterface $formState,
    $formId
  ) {
    // If exists janrain_connect validate, use it to get messages.
    if (Drupal::moduleHandler()->moduleExists('janrain_connect_validate')) {
      // Use direct access because is possible uninstall the Janrain Connect
      // Validate. @codingStandardsIgnoreLine
      $messages = Drupal::service('janrain_connect_validate.messages_mapping')
        ->getMessagesFields($serviceResult, $formId);
    }
    else {
      $messages[$formId] = t('Occurred an error on submit the form');
    }

    $formConfigurationFields = self::getConfigurationFieldsByFormId($formId);
    $configurationForm = self::getConfigurationFormByFormId($formId);

    foreach ($messages as $fieldId => $fieldMessages) {
      if (is_string($fieldMessages)) {
        $fieldMessages = [
          $fieldMessages,
        ];
      }
      foreach ($fieldMessages as $fieldMessage) {
        self::setFormError(
          $formState,
          $formId,
          $configurationForm,
          $formConfigurationFields,
          $fieldId,
          $fieldMessage
        );
      }
    }
  }

  /**
   * Get configuration form by form id.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form State.
   * @param string $formId
   *   Form id.
   * @param array $configurationForm
   *   Form configuration.
   * @param array $formConfigurationFields
   *   Form configuration fields.
   * @param string $fieldId
   *   The field id.
   * @param string $fieldMessage
   *   The field message.
   */
  public static function setFormError(
    FormStateInterface $formState,
    $formId,
    array $configurationForm,
    array $formConfigurationFields,
    $fieldId,
    $fieldMessage
  ) {
    $validationUpdateMessage = self::getValidationUpdateMessageFromConfigurationFields(
      $formConfigurationFields,
      $fieldId,
      $fieldMessage
    );
    if (!empty($validationUpdateMessage)) {
      $fieldMessage = $validationUpdateMessage;
    }

    if ($fieldId === $formId) {
      $defaultFieldError = self::getConfigurationByName(
        $configurationForm,
        'default_field_error'
      );
      if (!empty($defaultFieldError)) {
        $fieldId = $defaultFieldError;
      }
    }

    // @codingStandardsIgnoreLine.
    $formState->setErrorByName($fieldId, t($fieldMessage));
  }

  /**
   * Get configuration form by form id.
   *
   * @param array $formConfigurationFields
   *   Form configuration fields.
   * @param string $fieldId
   *   The field id.
   * @param string $fieldMessage
   *   The field message.
   *
   * @return string|null
   *   Validation update message or null.
   */
  private static function getValidationUpdateMessageFromConfigurationFields(
    array $formConfigurationFields,
    $fieldId,
    $fieldMessage
  ) {
    return !empty($formConfigurationFields[$fieldId]['validation-update-message'][$fieldMessage]) ?
      $formConfigurationFields[$fieldId]['validation-update-message'][$fieldMessage] : NULL;
  }

  /**
   * Get configuration forms.
   *
   * @return array
   *   Forms configuration.
   */
  public static function getConfigurationForms() {
    return Yaml::parse(Drupal::service('config.factory')
      ->get('janrain_connect.settings')
      ->get('configuration_forms'));
  }

  /**
   * Get configuration form by form id.
   *
   * @param string $formId
   *   The form id to obtain configuration form.
   *
   * @return array
   *   Form configuration.
   */
  public static function getConfigurationFormByFormId($formId) {
    if (empty($formId)) {
      return [];
    }
    $configurationForms = self::getConfigurationForms();
    return !empty($configurationForms[$formId]) ?
      $configurationForms[$formId] : [];
  }

  /**
   * Get configuration by name.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $name
   *   Configuration name.
   * @param mixed $defaultValue
   *   Configuration value default.
   *
   * @return mixed
   *   Configuration by name or default.
   */
  public static function getConfigurationByName(array $configuration, $name, $defaultValue = NULL) {
    return isset($configuration[$name]) ? $configuration[$name] : $defaultValue;
  }

  /**
   * Get configuration fields.
   *
   * @return array
   *   Form configuration fields.
   */
  public static function getConfigurationFields() {
    return Yaml::parse(Drupal::service('config.factory')
      ->get('janrain_connect.settings')
      ->get('configuration_fields'));
  }

  /**
   * Get configuration fields by form id.
   *
   * @param string $formId
   *   The form id to obtain configuration fields.
   *
   * @return array
   *   Form configuration fields.
   */
  public static function getConfigurationFieldsByFormId($formId) {
    if (empty($formId)) {
      return [];
    }
    $configurationFields = self::getConfigurationFields();
    return !empty($configurationFields[$formId]) ?
      $configurationFields[$formId] : [];
  }

}
