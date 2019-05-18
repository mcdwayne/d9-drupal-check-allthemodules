<?php

namespace Drupal\media_entity_slideshare\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid SlideShare embed code/URL.
 *
 * @constraint(
 *   id = "SlideShareEmbedCode",
 *   label = @Translation("SlideShare embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class SlideShareEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid SlideShare URL/Embed code.';

}
