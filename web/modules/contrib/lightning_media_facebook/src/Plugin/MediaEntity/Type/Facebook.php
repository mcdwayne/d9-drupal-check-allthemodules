<?php

namespace Drupal\lightning_media_facebook\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_facebook\Plugin\MediaEntity\Type\Facebook as BaseFacebook;

/**
 * Input-matching version of the Facebook media type.
 */
class Facebook extends BaseFacebook implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
