<?php
/**
 * @file
 * Contains \Drupal\php_password\Password\Drupal8Password.
 */

namespace Drupal\php_password\Password;

use Drupal\Core\Password\PasswordInterface;

class Drupal8Password implements PasswordInterface {

  /**
   * The Drupal 7 password hashing service.
   *
   * @var \Drupal\Core\Password\PhpassHashedPassword
   */
  protected $drupal7Password;

  /**
   * The PHP password hashing service.
   *
   * @var \Drupal\php_password\Password\PHPPassword
   */
  protected $phpPassword;

  /**
   * Constructs a new password hashing instance.
   *
   * @param \Drupal\Core\Password\PasswordInterface $php_password
   *   The PHP password hashing service.
   * @param \Drupal\Core\Password\PasswordInterface $drupal7_password
   *   The Drupal7 password hashing service.
   */
  function __construct(PasswordInterface $php_password, PasswordInterface $drupal7_password) {
    $this->phpPassword = $php_password;
    $this->drupal7Password = $drupal7_password;
  }

  /**
   * {@inheritdoc}
   */
  public function hash($password) {
    return $this->phpPassword->hash($password);
  }

  /**
   * {@inheritdoc}
   */
  public function check($password, $hash) {

    // MD5 migrated password (Drupal 6).
    if (substr($hash, 0, 2) == 'U$') {
      $hash = substr($hash, 1);
      $password = md5($password);
    }

    switch (substr($hash, 0, 2)) {
      case '$S':
      case '$H':
      case '$P':
        return $this->drupal7Password->check($password, $hash);

      default:
        return $this->phpPassword->check($password, $hash);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function needsRehash($hash) {
    return $this->phpPassword->needsRehash($hash);
  }

}
