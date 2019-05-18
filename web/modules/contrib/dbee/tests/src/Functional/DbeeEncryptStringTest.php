<?php

namespace Drupal\Tests\dbee\Functional;

/**
 * Encryption/Decryption.
 *
 * Check unit tests on the encryption/decryption functions with various entries.
 *
 * @group dbee
 */
class DbeeEncryptStringTest extends DbeeWebTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dbee'];

  /**
   * Check results from dbee_encrypt() and dbee_decrypt()funtions.
   */
  public function testMailEncryption() {
    // 'example@example.com'; // Lowercase.
    $mail = $this->lowercaseEmail;
    $crypted_mail = dbee_encrypt($mail);
    $validator = $this->container->get('email.validator');
    $result = ($mail != $crypted_mail && $validator->isValid($mail) && !$validator->isValid($crypted_mail));
    $message = 'A valid email address is encrypted,';
    $this->assertTrue($result, $message);

    $decrypted = dbee_decrypt($crypted_mail);
    $result = ($decrypted === $mail);
    $message = 'And it can be decrypted back.';
    $this->assertTrue($result, $message);

    // ''; // Empty.
    $mail_empty = $this->emptyEmail;
    $crypted_mail_empty = dbee_encrypt($mail_empty);
    // NULL.
    $mail_null = NULL;
    $crypted_mail_null = dbee_encrypt($mail_null);
    $result = ($mail_empty === $crypted_mail_empty && $mail_null === $crypted_mail_null);
    $message = 'If the email is empty or NULL, the encryption is not fired.';
    $this->assertTrue($result, $message);

    // Already crypted.
    $mail_test_crypted = $crypted_mail;
    $crypted_test_crypted = dbee_encrypt($mail_test_crypted);
    $result = ($mail_test_crypted === $crypted_test_crypted);
    $message = "If the email is already crypted, the crypted function returns the same value (don't encrypt it twice).";
    $this->assertTrue($result, $message);

    $mail_invalid = $this->invalidEmail;
    $crypted_invalid = dbee_encrypt($mail_invalid);
    $result = ($mail_invalid === $crypted_invalid);
    $message = "If the email is invalid, the crypted function returns the same value (don't encrypt invalid emails).";
    $this->assertTrue($result, $message);

    $mail_case = $this->sensitivecaseEmail;
    $crypted_case = dbee_encrypt($mail_case);
    $result = ($crypted_mail !== $crypted_case && $validator->isValid($mail_case) && !$validator->isValid($crypted_case));
    $message = 'If the email is sensitive case, 2 dictinct encrypted versions exist.';
    $this->assertTrue($result, $message);

    $decrypted_case = dbee_decrypt($crypted_case);
    $result = ($decrypted_case == $mail_case);
    $message = 'And the sensitive case email can be decrypted back.';
    $this->assertTrue($result, $message);
  }

}
