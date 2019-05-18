<?php

namespace Drupal\partner_links;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Partner entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup partner_links
 */
interface PartnerInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
