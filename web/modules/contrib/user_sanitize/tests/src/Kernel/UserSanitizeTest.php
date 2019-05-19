<?php

namespace Drupal\Tests\user_sanitize\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Component\Utility\Random;
use Drupal\user\Entity\User;

/**
 * Tests that user sanitizations are being carried out correctly.
 *
 * @group user_sanitize
 */
class UserSanitizeTest extends EntityKernelTestBase {

  /**
   * The number of dummy users to test with.
   */
  const TEST_USERS_COUNT = 10;

  /**
   * Array of test accounts.
   *
   * @var \Drupal\user\Entity\User[]
   */
  private $accounts;

  /**
   * Random utility class instance.
   *
   * @var \Drupal\Component\Utility\Random
   */
  private $random;

  /**
   * User sanitize mutable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->random = new Random();

    // Install the module and config.
    $this->installModule('user_sanitize');
    $this->installConfig(['user_sanitize']);

    // Get an editable instance of the module's config.
    $this->config = \Drupal::configFactory()->getEditable('user_sanitize.settings');

    // Create an admin user. (this is never sanitized, but increments the user
    // ID counter by 1, so we can do our tests on the right number of users.
    $this->createUser(['name' => 'testadmin', ['administer site content']]);
  }

  /**
   * Test the sanitization of the account name.
   */
  public function testAccountNameSanitization() {
    // Enable the name sanitizer, and disable the others.
    $this->config->set('fields.name.enabled', TRUE);
    $this->config->set('fields.pass.enabled', FALSE);
    $this->config->set('fields.mail.enabled', FALSE);
    $this->config->save();

    // Generate a batch of users.
    $this->generateTestUsers();

    // Sanitize using the above settings.
    user_sanitize_trigger_sanitization();

    // Check the name field is different to the original.
    $this->fieldEquality('name', FALSE);

    // Check all fields are still the same (have not been sanitized).
    $this->fieldEquality('pass');
    $this->fieldEquality('mail');
  }

  /**
   * Test the sanitization of the account pass.
   */
  public function testPasswordSanitization() {
    // Enable the pass sanitizer, and disable the others.
    $this->config->set('fields.pass.enabled', TRUE);
    $this->config->set('fields.mail.enabled', FALSE);
    $this->config->set('fields.name.enabled', FALSE);
    $this->config->save();

    // Generate a batch of users.
    $this->generateTestUsers();

    // Sanitize using the above settings.
    user_sanitize_trigger_sanitization();

    // Check the pass field is different to the original.
    $this->fieldEquality('pass', FALSE);

    // Check all fields are still the same (have not been sanitized).
    $this->fieldEquality('name');
    $this->fieldEquality('mail');
  }

  /**
   * Test the sanitization of the account email.
   */
  public function testMailSanitization() {
    // Enable the pass sanitizer, and disable the others.
    $this->config->set('fields.mail.enabled', TRUE);
    $this->config->set('fields.pass.enabled', FALSE);
    $this->config->set('fields.name.enabled', FALSE);
    $this->config->save();

    // Generate a batch of users.
    $this->generateTestUsers();

    // Sanitize using the above settings.
    user_sanitize_trigger_sanitization();

    // Check the mail field is different to the original.
    $this->fieldEquality('mail', FALSE);

    // Check all fields are still the same (have not been sanitized).
    $this->fieldEquality('pass');
    $this->fieldEquality('name');
  }

  /**
   * Test the sanitization of the all fields.
   */
  public function testAllSanitization() {
    // Enable all sanitizers.
    $this->config->set('fields.mail.enabled', TRUE);
    $this->config->set('fields.pass.enabled', TRUE);
    $this->config->set('fields.name.enabled', TRUE);
    $this->config->save();

    // Generate a batch of users.
    $this->generateTestUsers();

    // Sanitize using the above settings.
    user_sanitize_trigger_sanitization();

    // Check that all fields are different from the original.
    $this->fieldEquality('mail', FALSE);
    $this->fieldEquality('pass', FALSE);
    $this->fieldEquality('name', FALSE);
  }

  /**
   * Generate test users with a name, pass and email.
   *
   * Populates the accounts instance variable with an array of users. The array
   * key is the account id. Always creates TEST_USERS_COUNT accounts, and
   * multiple calls to this methods always returns the most recently created
   * accounts.
   */
  private function generateTestUsers() {
    // Remove any previous accounts from the instance variable.
    $this->accounts = NULL;

    // Repeat until all required accounts have been created.
    for ($i = 0; $i <= $this::TEST_USERS_COUNT; $i++) {
      // Create the user with random details.
      $account = $this->createUser([
        'name' => $this->random->name(8, TRUE),
        'pass' => $this->random->word(8),
        'mail' => strtolower($this->random->name(8, TRUE)) . '@domain.com',
      ]);
      // From the object, get the ID.
      $accountId = $account->id();
      // Sanitization never touches uid 1 (or less), so never add it.
      if ($accountId <= 1) {
        continue;
      }
      // Populate the array with the account.
      $this->accounts[$accountId] = $account;
    }
  }

  /**
   * Method which performs assertations on accounts.
   *
   * @param string $field_name
   *   The field name to test.
   * @param bool $match_expected
   *   Should the old value match the same value to be a successful test?
   */
  private function fieldEquality($field_name, $match_expected = TRUE) {
    // For each of the test accounts:
    foreach ($this->accounts as $accountId => $original_account) {
      // Load the account as-is within the system now.
      $current_account = User::load($accountId);
      if ($match_expected) {
        // Check if the field value is the same as when the account was created.
        $this->assertEquals($current_account->get($field_name)->value, $original_account->get($field_name)->value);
      }
      else {
        // Check the field value has been changed since the account was created.
        $this->assertNotEquals($current_account->get($field_name)->value, $original_account->get($field_name)->value);
      }
    }
  }

}
