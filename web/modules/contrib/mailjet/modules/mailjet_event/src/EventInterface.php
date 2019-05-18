<?php

namespace Drupal\mailjet_event;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Signupform entity.
 *
 */
interface EventInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
