<?php

namespace Drupal\media_entity_imgur\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid Imgur embed code/URL.
 *
 * @constraint(
 *   id = "ImgurEmbedCode",
 *   label = @Translation("Imgur embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class ImgurEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid Imgur URL/Embed code.';

}
