<?php

namespace Drupal\lightning_media_soundcloud\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_soundcloud\Plugin\MediaEntity\Type\Soundcloud as BaseSoundcloud;

/**
 * Input-matching version of the Soundcloud media type.
 */
class Soundcloud extends BaseSoundcloud implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
