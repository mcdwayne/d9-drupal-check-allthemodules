<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the owntracks waypoint interface.
 */
interface OwnTracksWaypointInterface extends ContentEntityInterface, EntityOwnerInterface, OwnTracksEntityInterface {
}
