<?php

/**
 * @file
 * Contains \Drupal\merci\Plugin\Validation\Constraint\MerciOpenHoursConstraintValidator.
 */

namespace Drupal\merci\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\office_hours\OfficeHoursDateHelper;

/**
 * Checks for conflicts when validating a entity with reservable items.
 */
class MerciOpenHoursConstraintValidator extends ConstraintValidator {

  private function renderOfficeHours($open_hours, $format = 'g:ia', $divider = '-') {
    $starthours = substr('0000' . $open_hours->starthours, -4);
    $endhours   = substr('0000' . $open_hours->endhours, -4);
    $start = DrupalDateTime::createFromFormat('Gi', $starthours);
    $end = DrupalDateTime::createFromFormat('Gi', $endhours);
    return $start->format($format) . $divider . $end->format($format);

  }

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
    /* @var \Drupal\Core\Entity\FieldableEntityInterface $referenced_entity */
    $referenced_entity = $value->entity;

    $datetime_start = $value->getEntity()->{$constraint->date_field}->first()->start_date;
    $datetime_end = $value->getEntity()->{$constraint->date_field}->first()->end_date;

    $datetime_start->setTimeZone(timezone_open(drupal_get_user_timezone()));
    $datetime_end->setTimeZone(timezone_open(drupal_get_user_timezone()));

    $start_day_of_week = $datetime_start->format('w');
    $end_day_of_week   = $datetime_end->format('w');

    $start_time = $datetime_start->format('Gi');
    $end_time   = $datetime_end->format('Gi');

    $start_valid = FALSE;
    $end_valid   = FALSE;

    $office_hours_field = $referenced_entity->{$constraint->reservable_hours_field}->first()->entity->{$constraint->office_hours_field};

    $office_hours = array();

    foreach ($office_hours_field as $open_hours) {
      if (!array_key_exists($open_hours->day, $office_hours)) {
        $office_hours[$open_hours->day] = array();
      }
      $office_hours[$open_hours->day][] = $open_hours;
      $starthours = substr('0000' . $open_hours->starthours, -4);
      $endhours   = substr('0000' . $open_hours->endhours, -4);

      if ($open_hours->day == $start_day_of_week) {
        if ($starthours <= $start_time && $endhours >= $start_time) {
          $start_valid = TRUE;
        }
      }
      if ($open_hours->day == $end_day_of_week) {
        if ($starthours <= $end_time && $endhours >= $end_time) {
          $end_valid = TRUE;
        }
      }
    }

    if ($start_valid == FALSE) {
      if (array_key_exists($start_day_of_week, $office_hours)) {
        foreach ($office_hours[$start_day_of_week] as $open_hours) {
          $message[] = $this->renderOfficeHours($open_hours);
        }
        $this->context->addViolation('Reservation begins at a time we are closed. We are open: %open', array('%open' => implode(' ', $message)));
      }
      else {
        $this->context->addViolation($constraint->message);
      }
    }

    if ($end_valid == FALSE) {
      if (array_key_exists($end_day_of_week, $office_hours)) {
        foreach ($office_hours[$end_day_of_week] as $open_hours) {
          $message[] = $this->renderOfficeHours($open_hours);
        }
        $this->context->addViolation('Reservation ends at a time we are closed. We are open: %open', array('%open' => implode(' ', $message)));
      }
      else {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}
