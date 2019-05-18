<?php

namespace Drupal\lightning_media_flickr\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_flickr\Plugin\MediaEntity\Type\Flickr as BaseFlickr;

/**
 * Input-matching version of the Flickr media type.
 */
class Flickr extends BaseFlickr implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
