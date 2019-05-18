<?php

namespace Drupal\contacts_events\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a booking window is unique.
 */
class BookingWindowsUniqueConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /* @var \Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItemList $items */
    // Track existing labels and cut offs.
    $properties = [
      'id' => 'messageId',
      'label' => 'messageLabel',
      'cut_off' => 'messageCutOff',
    ];
    $existing = array_fill_keys(array_keys($properties), []);
    $errors = [];

    // Loop over each item and property.
    foreach ($items as $item) {
      foreach ($properties as $property => $message) {
        // If it already exists, track for an error.
        if (in_array($item->{$property}, $existing[$property])) {
          $errors[$message] = $message;
        }
        // Otherwise track the value.
        else {
          $existing[$property][] = $item->{$property};
        }
      }
    }

    foreach ($errors as $message) {
      $this->context->addViolation($constraint->{$message});
    }
  }

}
