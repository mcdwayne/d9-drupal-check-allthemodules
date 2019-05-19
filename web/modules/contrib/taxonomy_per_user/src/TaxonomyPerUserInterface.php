<?php

namespace Drupal\taxonomy_per_user;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a TaxonomyPerUser entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup taxonomy_per_user
 */
interface TaxonomyPerUserInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
