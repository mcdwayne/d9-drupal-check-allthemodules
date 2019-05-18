<?php

namespace Drupal\image_moderate;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Contact entity.
 *
 * @ingroup image_moderate
 */
interface ImageModerateInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
