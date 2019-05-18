<?php

namespace Drupal\lightning_media_d500px\Plugin\MediaEntity\Type;

use Drupal\lightning_media\InputMatchInterface;
use Drupal\lightning_media\ValidationConstraintMatchTrait;
use Drupal\media_entity_d500px\Plugin\MediaEntity\Type\D500px as BaseD500px;

/**
 * Input-matching version of the 500px media type.
 */
class D500px extends BaseD500px implements InputMatchInterface {

  use ValidationConstraintMatchTrait;

}
