<?php

namespace Drupal\pwned_passwords;

/**
 * Interface PwnedPasswordCheckerControllerInterface.
 */
interface PwnedPasswordCheckerControllerInterface {

  /**
   * @param string $plaintext_password
   * @param int $pwned_threshold
   *
   * @return bool
   */
  public function isPasswordPwned(string $plaintext_password, int $pwned_threshold = 0): bool;

  /**
   * @param string $plaintext_password
   *
   * @return int
   */
  public function getPasswordPwnage(string $plaintext_password): int;
}
