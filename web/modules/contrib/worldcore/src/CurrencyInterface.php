<?php

namespace Drupal\worldcore;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Currency entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup worldcore
 */
interface CurrencyInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
