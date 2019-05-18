<?php

namespace Drupal\contacts_events\Plugin\Validation\Constraint;

use Drupal\datetime\Plugin\Validation\Constraint\DateTimeFormatConstraint as CoreConstraint;

/**
 * Validation constraint for DateTime items to ensure the format is correct.
 *
 * @Constraint(
 *   id = "ContactsEventsDateTimeFormat",
 *   label = @Translation("Datetime format valid for datetime type.", context = "Validation"),
 * )
 */
class DateTimeFormatConstraint extends CoreConstraint {

  /**
   * The property to validate.
   *
   * @var string
   */
  public $property = "value";

}
