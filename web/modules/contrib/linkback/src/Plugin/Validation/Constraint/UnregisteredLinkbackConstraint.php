<?php

namespace Drupal\linkback\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating linkback not registered.
 *
 * @Constraint(
 *   id = "UnregisteredLinkback",
 *   label = @Translation("Linkback not registered", context = "Validation"),
 *   type = "entity:linkback"
 * )
 */
class UnregisteredLinkbackConstraint extends Constraint {

  /**
   * Message shown when a linkback is already registered.
   *
   * @var string
   */
  public $linkbackRegistered = 'The %handler linkback from url (%url) to content with id %ref_content is already registered.';

}
