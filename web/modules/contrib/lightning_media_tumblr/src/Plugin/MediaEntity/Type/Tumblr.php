<?php

namespace Drupal\lightning_media_tumblr\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_tumblr\Plugin\MediaEntity\Type\Tumblr as BaseTumblr;

/**
 * Input-matching version of the Tumblr media type.
 */
class Tumblr extends BaseTumblr implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
