<?php

/**
 * @file
 * Contains \Drupal\merci\Plugin\Validation\Constraint\MerciMaxLengthConstraintValidator.
 */

namespace Drupal\merci\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Checks for conflicts when validating a entity with reservable items.
 */
class MerciMaxLengthConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }
    $id = $value->target_id;
    // '0' or NULL are considered valid empty references.
    if (empty($id)) {
      return;
    }

    $datetime_start = clone $value->getEntity()->{$constraint->date_field}->first()->start_date;
    $datetime_end   = $value->getEntity()->{$constraint->date_field}->first()->end_date;

    if ($constraint->grouping_field) {
      $entity = $value->entity->{$constraint->grouping_field}->first()->entity;
    }
    else {
      $entity = $value->entity;
    }

    $interval = $entity->{$constraint->interval_field}->first();

    if ($interval == NULL) {
      return;
    }

    $interval_spec = "P";

    if (in_array($interval->period, array('hour', 'minute', 'second'))) {
      $interval_spec .= 'T';
    }

    $interval_spec .= $interval->interval;

    $interval_map = array(
      'second' => 'S',
      'minute' => 'M',
      'hour' => 'H',
      'week' => 'W',
      'day' => 'D',
      'month' => 'M',
      'year' => 'Y',
    );

    $interval_spec .= $interval_map[$interval->period];

    $datetime_start->add(new \DateInterval($interval_spec));

    if ($datetime_start < $datetime_end) {
      $this->context->addViolation($constraint->message, array('@interval' => $interval->interval, '@period' => $interval->period));
    }

  }
}
