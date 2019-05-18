<?php

namespace Drupal\sms_phone_number\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\phone_number\Exception\PhoneNumberException;
use Drupal\sms_phone_number\SmsPhoneNumberUtilInterface;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a SMS Phone Number.
 *
 * Validates:
 *   - Number validity.
 *   - Allowed country.
 *   - Uniqueness.
 *   - Verification flood.
 *   - Phone number verification.
 */
class SmsPhoneNumberValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    /** @var \Drupal\sms_phone_number\Plugin\Field\FieldType\SmsPhoneNumberItem $item */
    $values = $item->getValue();
    if ((empty($values['value']) && empty($values['local_number']))) {
      return;
    }

    /** @var \Drupal\sms_phone_number\SmsPhoneNumberUtilInterface $util */
    $util = \Drupal::service('sms_phone_number.util');

    $field_label = $item->getFieldDefinition()->getLabel();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $item->getEntity();
    $entity_type = $entity->getEntityType()->getLowercaseLabel();
    $allowed_countries = $item->getFieldDefinition()->getSetting('allowed_countries');
    $verify = $item->getFieldDefinition()->getSetting('verify');
    $unique = $item->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSetting('unique');
    $tfa = $item->get('tfa')->getValue();

    try {
      $phone_number = $item->getPhoneNumber(TRUE);
      $country = $util->getCountry($phone_number);
      $display_number = $util->libUtil()->format($phone_number, PhoneNumberFormat::NATIONAL);
      if ($allowed_countries && !in_array($util->getCountry($phone_number), $allowed_countries)) {
        $this->context->addViolation($constraint->allowedCountry, [
          '@value' => $util->getCountryName($country),
          '@field_name' => Unicode::strtolower($field_label),
        ]);
      }
      else {
        $bypass_verification = \Drupal::currentUser()->hasPermission('bypass phone number verification requirement');
        $verification = $item->verify();

        if ($verification === -1) {
          $this->context->addViolation($constraint->flood, [
            '@value' => $display_number,
            '@field_name' => Unicode::strtolower($field_label),
          ]);
        }
        elseif ($verification === FALSE) {
          $this->context->addViolation($constraint->verification, [
            '@value' => $display_number,
            '@field_name' => Unicode::strtolower($field_label),
          ]);
        }
        elseif (!$verification && !$bypass_verification && ($tfa || $verify === SmsPhoneNumberUtilInterface::PHONE_NUMBER_VERIFY_REQUIRED)) {
          $this->context->addViolation($constraint->verifyRequired, [
            '@value' => $display_number,
            '@entity_type' => $entity_type,
            '@field_name' => Unicode::strtolower($field_label),
          ]);
        }
        elseif ($unique && !$item->isUnique($unique)) {
          $this->context->addViolation($constraint->unique, [
            '@value' => $display_number,
            '@entity_type' => $entity_type,
            '@field_name' => Unicode::strtolower($field_label),
          ]);
        }
      }
    }
    catch (PhoneNumberException $e) {
      $this->context->addViolation($constraint->validity, [
        '@value' => $values['local_number'],
        '@entity_type' => $entity_type,
        '@field_name' => Unicode::strtolower($field_label),
        '@message' => t($e->getMessage()),
      ]);
    }
  }

}
