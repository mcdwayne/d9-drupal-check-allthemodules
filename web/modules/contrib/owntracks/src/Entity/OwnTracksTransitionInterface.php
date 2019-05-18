<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the owntracks transition interface.
 */
interface OwnTracksTransitionInterface extends ContentEntityInterface, EntityOwnerInterface, OwnTracksEntityInterface {
}
