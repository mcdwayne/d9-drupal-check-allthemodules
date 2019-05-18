<?php

namespace Drupal\open_connect\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a open connect entity.
 */
interface OpenConnectInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the identity provider.
   *
   * @return string
   *   The identity provider.
   */
  public function getProvider();

  /**
   * Sets the identity provider.
   *
   * @param string $provider
   *   The identity provider.
   *
   * @return $this
   */
  public function setProvider($provider);

  /**
   * Gets the openid.
   *
   * @return string
   *   The openid.
   */
  public function getOpenid();

  /**
   * Sets the openid.
   *
   * @param string $openid
   *   The openid.
   *
   * @return $this
   */
  public function setOpenid($openid);

  /**
   * Gets the unionid of WeChat.
   *
   * @return string
   *   The unionid.
   */
  public function getUnionid();

  /**
   * Sets the unionid of WeChat.
   *
   * @param string $unionid
   *   The unionid.
   *
   * @return $this
   */
  public function setUnionid($unionid);

  /**
   * Gets the account user.
   *
   * @return \Drupal\user\UserInterface|null
   *   The account user entity, or NULL if not found,
   */
  public function getAccount();

  /**
   * Sets the account user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account user entity.
   *
   * @return $this
   */
  public function setAccount(UserInterface $account);

  /**
   * Gets the account user ID.
   *
   * @return int|null
   *   The account user ID, or NULL if not found.
   */
  public function getAccountId();

}
