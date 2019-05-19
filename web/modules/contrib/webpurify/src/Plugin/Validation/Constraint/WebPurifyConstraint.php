<?php

namespace Drupal\webpurify\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 *
 * @Constraint(
 *   id = "WebPurifyValidation",
 *   label = @Translation("Web Purify validation", context = "Validation"),
 * )
 */
class WebPurifyConstraint extends Constraint {
  public $message = '';
}
