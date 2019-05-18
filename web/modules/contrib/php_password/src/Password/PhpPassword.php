<?php

/**
 * @file
 * Contains \Drupal\php_password\Password\PhpPassword.
 */

namespace Drupal\php_password\Password;
use Drupal\Core\Password\PasswordInterface;

/**
 * Secure password hashing functions based on PHP (>=5.5.0) password hashing
 * functions.
 *
 * @see http://php.net/manual/en/ref.password.php
 */
class PhpPassword implements PasswordInterface {

  /**
   * The algorithmic cost that should be used. This is the same 'cost' option as
   * is used by the PHP (>= 5.5.0) password_hash() function.
   *
   * @var int
   *
   * @see password_hash().
   * @see http://php.net/manual/en/ref.password.php
   */
  protected $cost;

  /**
   * The algorithm constant used to hash password.
   *
   * @var int
   *
   * @see password_hash().
   * @see http://php.net/manual/en/password.constants.php
   */
  protected $algorithm;

  /**
   * Constructs a new password hashing instance.
   *
   * @param int $cost
   *   The algorithmic cost that should be used.
   * @param int $algorithm
   *   The hashing algorithm to use. Defaults to php default.
   */
  function __construct($cost, $algorithm = PASSWORD_DEFAULT) {
    $this->cost = $cost;
    $this->algorithm = $algorithm;
  }

  /**
   * {@inheritdoc}
   */
  public function hash($password) {
    // Prevent DoS attacks by refusing to hash large passwords.
    if (strlen($password) > static::PASSWORD_MAX_LENGTH) {
      return FALSE;
    }

    return password_hash($password, $this->algorithm, $this->getOptions());
  }

  /**
   * {@inheritdoc}
   */
  public function check($password, $hash) {
    return password_verify($password, $hash);
  }

  /**
   * {@inheritdoc}
   */
  public function needsRehash($hash) {
    // The PHP 5.5 password_needs_rehash() will return TRUE in two cases:
    // - The password is a Drupal 6 or 7 password and it has been rehashed
    //   during the migration. In this case the rehashed legacy hash is prefixed
    //   to indicate an old Drupal hash and will not comply with the expected
    //   password_needs_rehash() format.
    // - The parameters of hashing engine were changed. For example the
    //   parameter 'password_hash_cost' (the hashing cost) has been increased in
    //   core.services.yml.
    return password_needs_rehash($hash, PASSWORD_DEFAULT, $this->getOptions());
  }

  /**
   * Returns password options.
   *
   * @return array
   *   Associative array with password options.
   */
  protected function getOptions() {
    return ['cost' => $this->cost];
  }

}
