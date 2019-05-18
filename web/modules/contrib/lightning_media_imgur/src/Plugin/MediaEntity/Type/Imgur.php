<?php

namespace Drupal\lightning_media_imgur\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_imgur\Plugin\MediaEntity\Type\Imgur as BaseImgur;

/**
 * Input-matching version of the Imgur media type.
 */
class Imgur extends BaseImgur implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
