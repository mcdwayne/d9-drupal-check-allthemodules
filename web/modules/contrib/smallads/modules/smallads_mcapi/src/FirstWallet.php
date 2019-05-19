<?php

namespace Drupal\smallads_mcapi;

use Drupal\smallads\Entity\Smallad;

/**
 * Compatibility with mutual_credit package
 */
class FirstWallet {

  /**
   * DefaultValueCallback for wallet field.
   *
   * Get the first wallet of the owner of the given smallad.
   *
   * @param Smallad $entity
   *
   * @return array
   *   The wallet ID
   */
  static function get(Smallad $entity) {
    $uid = $entity->getOwnerId();
    $wids = \Drupal::entityTypeManager()->getStorage('mcapi_wallet')->myWallets($uid);
    //N.B. this NUST be an array
    return array_slice($wids, 0, 1);
  }

}
