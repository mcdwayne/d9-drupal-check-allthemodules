<?php

namespace Drupal\worldcore;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Payment entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup worldcore
 */
interface PaymentInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
