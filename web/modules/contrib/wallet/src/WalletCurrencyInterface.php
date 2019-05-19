<?php

namespace Drupal\wallet;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Wallet entity.
 *
 * @ingroup wallet
 */
interface WalletCurrencyInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
