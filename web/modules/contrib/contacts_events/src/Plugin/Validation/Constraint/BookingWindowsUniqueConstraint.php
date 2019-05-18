<?php

namespace Drupal\contacts_events\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for unique booking windows on a single entity.
 *
 * @Constraint(
 *   id = "BookingWindowsUnique",
 *   label = @Translation("Booking windows are unique.", context = "Validation"),
 * )
 */
class BookingWindowsUniqueConstraint extends Constraint {

  /**
   * Message for when the IDs aren't unique.
   *
   * @var string
   */
  public $messageId = "Booking window IDs must be unique.";

  /**
   * Message for when the labels aren't unique.
   *
   * @var string
   */
  public $messageLabel = "Booking window labels must be unique.";

  /**
   * Message for when the cut offs aren't unique.
   *
   * @var string
   */
  public $messageCutOff = "Booking window cut offs must be unique.";

}
