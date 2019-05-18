<?php

namespace Drupal\rng_contact\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a email contact entity.
 */
interface RngContactInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
