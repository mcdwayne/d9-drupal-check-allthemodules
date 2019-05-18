<?php

namespace Drupal\linkback\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating linkback not registered.
 *
 * @Constraint(
 *   id = "DisabledReceiveLinkback",
 *   label = @Translation("Linkback receive disabled", context = "Validation"),
 *   type = "entity:linkback"
 * )
 */
class DisabledReceiveLinkbackConstraint extends Constraint {

  /**
   * Message shown when a linkback receive is not enabled for this content id.
   *
   * @var string
   */
  public $linkbackDisabled = 'Content with id %ref_content has the receive linkbacks disabled.';

}
