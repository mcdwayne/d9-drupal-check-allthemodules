<?php

namespace Drupal\nodeownership;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Claim entity.
 *
 * @ingroup Nodeownership
 */
interface NodeownershipClaimInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
