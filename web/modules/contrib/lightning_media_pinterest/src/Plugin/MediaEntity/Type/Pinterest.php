<?php

namespace Drupal\lightning_media_pinterest\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_pinterest\Plugin\MediaEntity\Type\Pinterest as BasePinterest;

/**
 * Input-matching version of the Pinterest media type.
 */
class Pinterest extends BasePinterest implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
