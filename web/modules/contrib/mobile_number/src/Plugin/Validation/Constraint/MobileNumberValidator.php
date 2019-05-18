<?php

namespace Drupal\mobile_number\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\mobile_number\Exception\MobileNumberException;
use Drupal\mobile_number\MobileNumberUtilInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a mobile number.
 *
 * Validates:
 *   - Number validity
 *   - Allowed country
 *   - Verification flood
 *   - Mobile number verification
 *   - Uniqueness.
 */
class MobileNumberValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    /** @var \Drupal\mobile_number\Plugin\Field\FieldType\MobileNumberItem $item */
    $values = $item->getValue();
    if ((empty($values['value']) && empty($values['local_number']))) {
      return;
    }

    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');

    $field_label = $item->getFieldDefinition()->getLabel();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $item->getEntity();
    $entity_type = $entity->getEntityType()->getLowercaseLabel();
    $allowed_countries = $item->getFieldDefinition()->getSetting('countries');
    $verify = $item->getFieldDefinition()->getSetting('verify');
    $unique = $item->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getSetting('unique');
    $tfa = $item->get('tfa')->getValue();

    try {
      $mobile_number = $item->getMobileNumber(TRUE);
      $country = $util->getCountry($mobile_number);
      $display_number = $util->libUtil()->format($mobile_number, 2);
      if (!in_array($util->getCountry($mobile_number), $allowed_countries) && $allowed_countries) {
        $this->context->addViolation($constraint->allowedCountry, [
          '@value' => $util->getCountryName($country),
          '@field_name' => Unicode::strtolower($field_label),
        ]);
      }
      else {
        $bypass_verification = \Drupal::currentUser()->hasPermission('bypass mobile number verification requirement');
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
        elseif (!$verification && !$bypass_verification && ($tfa || $verify === MobileNumberUtilInterface::MOBILE_NUMBER_VERIFY_REQUIRED)) {
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
    catch (MobileNumberException $e) {
      $this->context->addViolation($constraint->validity, [
        '@value' => $values['local_number'],
        '@entity_type' => $entity_type,
        '@field_name' => Unicode::strtolower($field_label),
        '@message' => t($e->getMessage()),
      ]);
    }
  }

}
