<?php

namespace Drupal\owntracks\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the owntracks location interface.
 */
interface OwnTracksLocationInterface extends ContentEntityInterface, EntityOwnerInterface, OwnTracksEntityInterface {
}
