<?php

namespace Drupal\legal;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Terms & Conditions entity.
 * @ingroup legal
 */
interface ConditionsInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
