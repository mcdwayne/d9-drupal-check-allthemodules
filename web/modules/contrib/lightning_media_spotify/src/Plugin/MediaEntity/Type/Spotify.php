<?php

namespace Drupal\lightning_media_spotify\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_spotify\Plugin\MediaEntity\Type\Spotify as BaseSpotify;

/**
 * Input-matching version of the Spotify media type.
 */
class Spotify extends BaseSpotify implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
