<?php

namespace Drupal\mobile_number\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\ConfigurableTargetInterface;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a mobile number field mapper.
 *
 * @FeedsTarget(
 *   id = "mobile_number",
 *   field_types = {"mobile_number"}
 * )
 */
class MobileNumber extends FieldTargetBase implements ConfigurableTargetInterface {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->addProperty('local_number')
      ->addProperty('country')
      ->addProperty('tfa')
      ->addProperty('verified');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    /** @var MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $mobile_number = FALSE;
    if (!empty($values['local_number']) && !empty($values['country'])) {
      $mobile_number = $util->getMobileNumber($values['local_number'], $values['country']);
    }
    else {
      $mobile_number = $util->getMobileNumber($values['value']);
    }
    if ($mobile_number) {
      $values['value'] = $util->getCallableNumber($mobile_number);
      $values['local_number'] = $util->getLocalNumber($mobile_number);
      $values['country'] = $util->getCountry($mobile_number);
      $values['tfa'] = !empty($values['tfa']) ? 1 : 0;
      if (!empty($values['verified'])) {
        $code = $util->generateVerificationCode();
        $token = $util->registerVerificationCode($mobile_number, $code);

        $values['verification_code'] = $code;
        $values['verification_token'] = $token;
      }
      $values['verified'] = 0;
    }
    else {
      $values = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return '';
  }

}
