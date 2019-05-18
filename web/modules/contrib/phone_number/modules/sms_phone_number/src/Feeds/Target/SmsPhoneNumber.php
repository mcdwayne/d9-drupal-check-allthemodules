<?php

namespace Drupal\sms_phone_number\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\phone_number\Feeds\Target\PhoneNumber;

/**
 * Defines a SMS Phone Number field mapper.
 *
 * @FeedsTarget(
 *   id = "sms_phone_number",
 *   field_types = {"sms_phone_number"}
 * )
 */
class SmsPhoneNumber extends PhoneNumber {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return parent::prepareTarget($field_definition)
      ->addProperty('tfa')
      ->addProperty('verified');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // Get the basics in place.
    parent::prepareValue($delta, $values);
    // Go further if we have anything further.
    if (!empty($values['verified']) || !empty($values['tfa'])) {
      /** @var SmsPhoneNumberUtilInterface $util */
      $util = \Drupal::service('sms_phone_number.util');
      $phone_number = FALSE;
      if (!empty($values['local_number']) && !empty($values['country'])) {
        $phone_number = $util->getPhoneNumber($values['local_number'], $values['country']);
      }
      else {
        $phone_number = $util->getPhoneNumber($values['value']);
      }
      if ($phone_number) {
        $values['tfa'] = !empty($values['tfa']) ? 1 : 0;
        if (!empty($values['verified'])) {
          $code = $util->generateVerificationCode();
          $token = $util->registerVerificationCode($phone_number, $code);
          $values['verification_code'] = $code;
          $values['verification_token'] = $token;
        }
        $values['verified'] = 0;
      }
    }
  }

}
