<?php

namespace Drupal\pwned_passwords;

use Esolitos\PwnedPasswords\PwnageValidator;

/**
 * Class PwnedPasswordCheckerController.
 */
class PwnedPasswordCheckerController implements PwnedPasswordCheckerControllerInterface {

  /**
   * @var \Esolitos\PwnedPasswords\PwnageValidator
   */
  protected $validator;

  public function __construct() {
    $this->validator = new PwnageValidator();
  }

  /**
   * @param string $plaintext_password
   * @param int $pwned_threshold
   *
   * @return bool
   */
  public function isPasswordPwned(string $plaintext_password, int $pwned_threshold = 0): bool {
    return $pwned_threshold <= $this->getPasswordPwnage($plaintext_password);
  }

  /**
   * @param string $plaintext_password
   *
   * @return int
   */
  public function getPasswordPwnage(string $plaintext_password): int {
    $drupal_static_fast['pwned'] = &drupal_static(__CLASS__ . __METHOD__);

    // If we have not checked the password al;ready fetch the info from the remote service.
    if (!isset($drupal_static_fast['pwned'][$plaintext_password])) {
      $drupal_static_fast['pwned'][$plaintext_password] = $this->validator->getPasswordPwnage($plaintext_password);
    }

    return $drupal_static_fast['pwned'][$plaintext_password];
  }


}
