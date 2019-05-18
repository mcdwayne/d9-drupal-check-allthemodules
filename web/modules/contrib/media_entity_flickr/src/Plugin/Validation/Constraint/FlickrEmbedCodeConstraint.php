<?php

namespace Drupal\media_entity_flickr\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid Flickr embed code/URL.
 *
 * @constraint(
 *   id = "FlickrEmbedCode",
 *   label = @Translation("Flickr embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class FlickrEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid Flickr URL/Embed code.';

}
