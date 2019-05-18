<?php

namespace Drupal\flow_player_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the Flow Player field.
 *
 * @Constraint(
 *   id = "FlowPlayerValidation",
 *   label = @Translation("Flowplayer provider constraint", context =
 *   "Validation"),
 * )
 */
class FlowPlayerConstraint extends Constraint {

  /**
   * Message shown when a video provider is not found.
   *
   * @var string
   */
  public $message = 'Could not find a video provider to handle the given URL.';

}
