<?php

namespace Drupal\coming_soon;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Subscriber entity.
 *
 * @ingroup coming_soon
 */
interface SubscriberInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
