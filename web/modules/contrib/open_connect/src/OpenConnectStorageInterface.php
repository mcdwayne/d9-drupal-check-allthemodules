<?php

namespace Drupal\open_connect;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an interface for open connect entity storage classes.
 */
interface OpenConnectStorageInterface extends ContentEntityStorageInterface {

  /**
   * Loads the open connect for the given provider and openid.
   *
   * @param string $provider
   *   The identity provider which the openid belongs to.
   * @param string $openid
   *   The openid.
   *
   * @return \Drupal\open_connect\Entity\OpenConnectInterface|null
   *   The open connect, or NULL if none found.
   */
  public function loadByOpenid($provider, $openid);

  /**
   * Loads the open connect for the given provider and openid.
   *
   * @param string $unionid
   *   The unionid.
   *
   * @return \Drupal\open_connect\Entity\OpenConnectInterface|null
   *   The open connect, or NULL if none found.
   */
  public function loadByUnionid($unionid);

  /**
   * Loads the open connect for the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *    The user entity.
   * @param string $provider
   *   (Optional) The identity provider.
   *
   * @return \Drupal\open_connect\Entity\OpenConnectInterface[]
   *   The open connect entities.
   */
  public function loadMultipleByUser(AccountInterface $account, $provider = NULL);

}
