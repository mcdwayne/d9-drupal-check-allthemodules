<?php

/**
 * @file
 * Contains \Drupal\merci\Plugin\Validation\Constraint\MerciAllowOvernightConstraintValidator.
 */

namespace Drupal\merci\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Checks for conflicts when validating a entity with reservable items.
 */
class MerciAllowOvernightConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */

    $constraint->overnight_field = 'field_merci_allow_overnight';
    if (!isset($value)) {
      return;
    }
    $id = $value->target_id;
    // '0' or NULL are considered valid empty references.
    if (empty($id)) {
      return;
    }

    if ($constraint->grouping_field) {
      $entity = $value->entity->{$constraint->grouping_field}->first()->entity;
    }
    else {
      $entity = $value->entity;
    }

    if ($entity->{$constraint->overnight_field}->value != 1) {

      $datetime_start = $value->getEntity()->{$constraint->date_field}->first()->start_date;
      $datetime_end   = $value->getEntity()->{$constraint->date_field}->first()->end_date;
      $datetime_start->setTimeZone(timezone_open(drupal_get_user_timezone()));
      $datetime_end->setTimeZone(timezone_open(drupal_get_user_timezone()));
      $date_format = DateFormat::load('html_date')->getPattern();
      if ($datetime_start->format($date_format) != $datetime_end->format($date_format)) {
        $this->context->addViolation($constraint->message);
      }
    }
  }
}
