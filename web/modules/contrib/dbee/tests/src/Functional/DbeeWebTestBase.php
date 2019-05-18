<?php

namespace Drupal\Tests\dbee\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class for the dbee modules tests.
 *
 * Correctly install sub modules, provide emails and a testing function.
 */
abstract class DbeeWebTestBase extends BrowserTestBase {

  /**
   * Modules to enable Do not enable the dbee module yet.
   *
   * @var array
   */
  protected static $modules = ['user', 'encrypt', 'real_aes'];

  /**
   * Simple Lowercase email adress.
   *
   * @var string
   */
  protected $lowercaseEmail = 'example@example.com';

  /**
   * Sensitive case Lowercase email adress.
   *
   * @var string
   */
  protected $sensitivecaseEmail = 'ExAMpLe@ExAMpLe.com';

  /**
   * Invalid email adress.
   *
   * @var string
   */
  protected $invalidEmail = 'exaMple!@example,com';

  /**
   * Empty email adress.
   *
   * @var string
   */
  protected $emptyEmail = '';

  /**
   * Set private directory for storing encryption key.
   *
   * @var string
   */
  protected function writeSettings(array $settings) {
    if (empty($settings['file_private_path'])) {
      $settings['file_private_path'] = (object) [
        'value' => $this->privateFilesDirectory . '/dbee',
        'required' => TRUE,
      ];
    }
    parent::writeSettings($settings);
  }

  /**
   * Make sure that the dbee functions are availables.
   */
  public function setUp() {
    parent::setUp();
    if (!function_exists('dbee_encrypt')) {
      module_load_include('module', 'dbee');
    }
  }

  /**
   * Check that the emails stored in db are the expected ones.
   *
   * Check the 4 values : mail, init, and the sensitive case mail (dbee). Try
   * to decrypt it back.
   *
   * @param array $usersInfo
   *   Multidimensional array storing user mail and init original values
   *   (uncrypted) keyed by the user ID.
   * @param bool $installed
   *   Inform if the dbee module is enabled. TRUE for enabled (datas should be
   *   encrypted, FALSE for disabled (datas should be decrypted). Default is
   *   TRUE.
   *
   * @return bool
   *   TRUE if all test users email addresses are as expected.
   */
  protected function dbeeAllUsersValid(array $usersInfo, $installed = TRUE) {
    // Test all email address.
    $all_succeed = TRUE;
    $dbee_fields = ['mail', 'init'];
    foreach ($usersInfo as $uid => $source) {
      // $source is the real values (uncrypted).
      $storeds = dbee_stored_users($uid);
      $stored = $storeds[$uid];
      foreach ($dbee_fields as $field) {
        // Set from the source if the data should be encrypted or not.
        $encryption_on = ($installed && dbee_email_to_alter($source[$field]));
        // Test if value seems encrypted.
        $decrypted_stored_data = dbee_decrypt($stored[$field]);
        $is_encrypted = (!empty($stored[$field]) && !dbee_email_to_alter($stored[$field]) && $stored[$field] != $source[$field]);
        $expected = ($decrypted_stored_data == $source[$field] && (($encryption_on && $is_encrypted) || (!$encryption_on && !$is_encrypted)));
        if (!$expected) {
          $all_succeed = FALSE;
          $crypted = (($is_encrypted) ? 'encrypted' : 'uncrypted');
          $expected_crypted = (($encryption_on) ? 'encrypted' : 'uncrypted');

          $this->assertTrue(FALSE, "User {$uid} : the stored {$field} ({$crypted} {$decrypted_stored_data}) is not the expected one ({$expected_crypted}, {$source[$field]})");
        }
      }
    }
    return $all_succeed;
  }

}
