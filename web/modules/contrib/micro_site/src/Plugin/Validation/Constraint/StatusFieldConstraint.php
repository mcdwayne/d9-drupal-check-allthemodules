<?php

namespace Drupal\micro_site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity field has a unique value.
 *
 * @Constraint(
 *   id = "StatusField",
 *   label = @Translation("Domain site status field constraint", context = "Validation"),
 * )
 */
class StatusFieldConstraint extends Constraint {

  public $message = "You can't publish a site not registered. Please register the site.";

}
