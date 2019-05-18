<?php

namespace Drupal\reference_map_adva\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the map's last step references a user entity.
 *
 * @Constraint(
 *   id = "ReferenceMapAdvaLastStepIsUser",
 *   label = @Translation("Reference Map Adavanced Access Last Step is User", context = "Validation"),
 *   type = "entity:reference_map_config"
 * )
 */
class ReferenceMapAdvaLastStepIsUser extends Constraint {

  /**
   * The message to show if the last step doesn't reference a user entity.
   *
   * @var string
   */
  public $message = "The last step of Advanced Access Reference Map Type plugins must reference a user entity.";

}
