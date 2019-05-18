<?php

namespace Drupal\open_connect;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for open connect.
 *
 * This extends the base storage class, adding required special handling for
 * open connect entities.
 */
class OpenConnectStorage extends SqlContentEntityStorage implements OpenConnectStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByOpenid($provider, $openid) {
    $entities = $this->loadByProperties([
      'provider' => $provider,
      'openid' => $openid,
    ]);
    $entity = reset($entities);

    return $entity ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUnionid($unionid) {
    $entities = $this->loadByProperties(['unionid' => $unionid]);
    $entity = reset($entities);

    return $entity ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByUser(AccountInterface $account, $provider = NULL) {
    $query = $this->getQuery()
      ->condition('uid', $account->id())
      ->sort('provider');
    if ($provider) {
      $query->condition('provider', $provider);
    }
    $result = $query->execute();

    return $result ? $this->loadMultiple($result) : [];
  }

}
