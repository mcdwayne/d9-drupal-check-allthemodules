<?php

namespace Drupal\pubkey_encrypt\Tests;

/**
 * Tests the management of users asymmetric keys.
 *
 * @group pubkey_encrypt
 */
class UserKeysManagement extends PubkeyEncryptTestBase {

  /**
   * Test initialization and protection of fields upon new user registration.
   */
  public function testNewUserRegistration() {
    // Create a user.
    $user = $this->drupalCreateUser(array());

    // Verify the initialization of fields.
    $this->assertEqual($user->get('field_private_key_protected')->getString(), "0", "User keys have not been protected initially");
    $this->assertFalse($user->get('field_private_key')->isEmpty(), "Private key has been initialized");
    $this->assertFalse($user->get('field_public_key')->isEmpty(), "Public key has been initialized");

    // First time user login.
    $this->drupalLogin($user);

    // Reload the user entity again.
    \Drupal::entityTypeManager()->getStorage('user')->resetCache();
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($user->id());

    // Verify the protection of fields.
    $this->assertEqual($user->get('field_private_key_protected')->getString(), "1", "User keys have been protected after first time login");
  }

  /**
   * Test the temporary storage of a user Private key upon login.
   */
  public function testPrivateKeyTemporaryStorage() {
    // Create a user.
    $user = $this->drupalCreateUser(array());

    // Store the original Private key of user.
    $privateKey = $user->get('field_private_key')->getString();

    // First time user login.
    $this->drupalLogin($user);

    // Fetch the temporarily stored Private key.
    $storedPrivateKey = $_COOKIE[\Drupal::currentUser()->id() . '_private_key'];

    $this->assertEqual($storedPrivateKey, $privateKey, "Private key is temporarily stored upon a user login.");
  }

  /**
   * Test re-protection of a user Private key upon credentials change.
   */
  public function testCredentialsChange() {
    $user = $this->drupalCreateUser(array('change own username'));
    $this->drupalLogin($user);

    $oldPassword = $user->pass_raw;

    // Reload the user entity.
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadUnchanged($user->id());

    // Fetch the Private key of user, protected with old credentials atm.
    $protectedPrivateKeyOld = $user
      ->get('field_private_key')
      ->getString();

    // Fetch the temporarily stored original Private key.
    $storedPrivateKeyOld = $_COOKIE[\Drupal::currentUser()->id() . '_private_key'];

    // Change user credentials.
    $edit = array();
    $edit['pass[pass1]'] = $newPassword = $this->randomMachineName();
    $edit['pass[pass2]'] = $newPassword;
    $edit['current_pass'] = $oldPassword;
    $this->drupalPostForm("user/" . $user->id() . "/edit", $edit, t('Save'));
    $this->assertRaw(t("The changes have been saved."));

    // Login the user again, this time with his new credentials.
    $this->drupalLogout();
    $user->pass_raw = $newPassword;
    $this->drupalLogin($user);

    // Reload the user entity again.
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadUnchanged($user->id());

    // Fetch the Private key of user, now protected with new credentials.
    $protectedPrivateKeyNew = $user
      ->get('field_private_key')
      ->getString();

    // Fetch the temporarily stored original Private key.
    $storedPrivateKey = $_COOKIE[\Drupal::currentUser()->id() . '_private_key'];

    $this->assertNotEqual($protectedPrivateKeyNew, $protectedPrivateKeyOld, "Credentials change re-protects the Private key of a user.");
    $this->assertEqual($storedPrivateKey, $storedPrivateKeyOld, "Credentials change does not modify the Private key of a user.");
  }

}
