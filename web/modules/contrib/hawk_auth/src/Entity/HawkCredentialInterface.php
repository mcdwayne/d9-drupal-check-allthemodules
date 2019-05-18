<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Entity\HawkCredentialInterface.
 */

namespace Drupal\hawk_auth\Entity;

use Dragooon\Hawk\Credentials\CredentialsInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Interface defines individual hawk credential's model.
 */
interface HawkCredentialInterface extends ContentEntityInterface, CredentialsInterface {

  /**
   * Returns the ID of the User this credential belongs to.
   *
   * @return int
   *   The ID of the owner.
   */
  public function getOwnerId();

  /**
   * Sets the ID of the User this credential belongs to.
   *
   * @param int $id
   *   The ID of the owner to set.
   *
   * @return $this
   *   HawkCredentialInterface object for chaining.
   */
  public function setOwnerId($id);

  /**
   * Returns the object of the user this credential belongs to.
   *
   * @return UserInterface
   *   The owner's object.
   */
  public function getOwner();

  /**
   * Sets the ID of the owner from the object of the user.
   *
   * @param UserInterface $account
   *   The owner to set.
   *
   * @return $this
   *   HawkCredentialInterface object for chaining.
   */
  public function setOwner(UserInterface $account);

  /**
   * Returns the key secret.
   *
   * @return string
   *   The key secret associated with this credential.
   */
  public function getKeySecret();

  /**
   * Sets the key secret.
   *
   * @param string $key_secret
   *   The key secret to set.
   *
   * @return $this
   *   HawkCredentialInterface object for chaining.
   */
  public function setKeySecret($key_secret);

  /**
   * Returns the algorithm for hashing.
   *
   * @return string
   *   The key algo associated with this credential.
   */
  public function getKeyAlgo();

  /**
   * Sets the algorithm for hashing.
   *
   * @param string $key_algo
   *   They key algo to set.
   *
   * @return $this
   *   HawkCredentialInterface object for chaining.
   */
  public function setKeyAlgo($key_algo);

  /**
   * Returns the permissions this credential is revoking.
   *
   * @return array
   *   The list of permissions being revoked.
   */
  public function getRevokePermissions();

  /**
   * Sets the permissions this credential will revoke.
   *
   * @param array $permissions
   *   The permissions to revoke.
   *
   * @return $this
   *   HawkCredentialInterface object for chaining.
   */
  public function setRevokePermissions(array $permissions);

  /**
   * Whether this credential revokes a certain permission or not.
   *
   * @param string $permission
   *   The permission to check.
   *
   * @return bool
   *   Whether this credential revokes this permission or not.
   */
  public function revokesPermission($permission);

}
