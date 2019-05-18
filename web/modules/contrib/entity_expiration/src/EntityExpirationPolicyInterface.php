<?php

namespace Drupal\entity_expiration;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a EntityExpirationPolicy entity.
 * @ingroup entity_expiration
 */
interface EntityExpirationPolicyInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}

?>