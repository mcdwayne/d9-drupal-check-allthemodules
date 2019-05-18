<?php

namespace Drupal\phone_number\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\phone_number\Exception\PhoneNumberException;
use libphonenumber\PhoneNumberFormat;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a phone number.
 *
 * Validates:
 *   - Number validity.
 *   - Allowed country.
 *   - Uniqueness.
 */
class PhoneNumberValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    /** @var \Drupal\phone_number\Plugin\Field\FieldType\PhoneNumberItem $item */
    $values = $item->getValue();
    if ((empty($values['value']) && empty($values['local_number']))) {
      return;
    }

    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');

    $field_label = $item->getFieldDefinition()->getLabel();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $item->getEntity();
    $entity_type = $entity->getEntityType()->getLowercaseLabel();
    $allowed_countries = $item->getFieldDefinition()->getSetting('allowed_countries');
    $unique = $item->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSetting('unique');

    try {
      $phone_number = $item->getPhoneNumber(TRUE);
      $country = $util->getCountry($phone_number);
      $display_number = $util->libUtil()->format($phone_number, PhoneNumberFormat::NATIONAL);
      if (!in_array($util->getCountry($phone_number), $allowed_countries) && $allowed_countries) {
        $this->context->addViolation($constraint->allowedCountry, [
          '@value' => $util->getCountryName($country),
          '@field_name' => Unicode::strtolower($field_label),
        ]);
      }
      elseif ($unique && !$item->isUnique()) {
        $this->context->addViolation($constraint->unique, [
          '@value' => $display_number,
          '@entity_type' => $entity_type,
          '@field_name' => Unicode::strtolower($field_label),
        ]);
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
