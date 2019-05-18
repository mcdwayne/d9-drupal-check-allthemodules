<?php

namespace Drupal\Tests\key_auth\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\key_auth\KeyAuth;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Tests for key authentication provider.
 *
 * @group key_auth
 */
class KeyAuthTest extends BrowserTestBase {

  /**
   * Modules installed for all tests.
   *
   * @var array
   */
  public static $modules = ['key_auth', 'key_auth_test'];

  /**
   * The key auth service.
   *
   * @var \Drupal\key_auth\KeyAuthInterface
   */
  protected $keyAuth;

  /**
   * The module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $keyAuthConfig;

  /**
   * User storage.
   *
   * @var \Drupal\user\userStorage
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->keyAuth = $this->container->get('key_auth');
    $this->keyAuthConfig = $this->config('key_auth.settings');
    $this->userStorage = $this->container->get('entity_type.manager')->getStorage('user');
  }

  /**
   * Test setting for key length.
   */
  public function testKeyLength() {
    // Set the key length to 64.
    $this->keyAuthConfig->set('key_length', 64);
    $this->keyAuthConfig->save();

    // Test the length.
    $this->assertTrue(strlen($this->keyAuth->generateKey()) == 64);
  }

  /**
   * Test automatically generating a key for new users.
   */
  public function testUserAutoKeyGeneration() {
    // Enable auto key generation.
    $this->keyAuthConfig->set('auto_generate_keys', TRUE);
    $this->keyAuthConfig->save();

    // Create a user with key authentication access.
    $user = $this->drupalCreateUser(['use key authentication']);

    // Check that a key is present.
    $this->assertNotEmpty($user->api_key->value);

    // Create a user without key authentication access.
    $user = $this->drupalCreateUser([]);

    // Check that a key is not present.
    $this->assertEmpty($user->api_key->value);

    // Disable auto key generation.
    $this->keyAuthConfig->set('auto_generate_keys', FALSE);
    $this->keyAuthConfig->save();

    // Grant access to use key auth to authenticated users.
    $user = $this->drupalCreateUser(['use key authentication']);

    // Check that a key is not present.
    $this->assertEmpty($user->api_key->value);
  }

  /**
   * Test random key generation.
   */
  public function testRandomKey() {
    $this->assertNotEquals($this->keyAuth->generateKey(), $this->keyAuth->generateKey());
  }

  /**
   * Test the user key auth form.
   */
  public function testUserKeyAuthForm() {
    // Enable both key detection methods.
    $this->keyAuthConfig->set('detection_methods', [
      KeyAuth::DETECTION_METHOD_HEADER,
      KeyAuth::DETECTION_METHOD_QUERY,
    ])->save();

    // Make sure the form is not accessible.
    $this->drupalGet(Url::fromRoute('key_auth.user_key_auth_form', ['user' => 1]));
    $this->assertSession()->statusCodeEquals(403);

    // Create a user without key auth access.
    $user1 = $this->drupalCreateUser([]);

    // Log in.
    $this->drupalLogin($user1);

    // Access should still be denied.
    $this->drupalGet(Url::fromRoute('key_auth.user_key_auth_form', ['user' => $user1->id()]));
    $this->assertSession()->statusCodeEquals(403);

    // Log out.
    $this->drupalLogout();

    // Create a user with key auth access.
    $user2 = $this->drupalCreateUser(['use key authentication']);

    // Log in.
    $this->drupalLogin($user2);

    // Set a key.
    $user2->set('api_key', $this->keyAuth->generateKey())->save();

    // Access should be granted.
    $this->drupalGet(Url::fromRoute('key_auth.user_key_auth_form', ['user' => $user2->id()]));
    $this->assertSession()->statusCodeEquals(200);

    // Check that the key is on the page.
    $this->assertSession()->pageTextContains($user2->api_key->value);

    // Check that both buttons appear.
    $this->assertSession()->elementExists('css', '#edit-new');
    $this->assertSession()->elementExists('css', '#edit-delete');

    // Test deleting the key.
    $this->drupalPostForm(NULL, [], 'Delete current key');
    $user2 = $this->loadUser($user2->id());
    $this->assertEmpty($user2->api_key->value);
    $this->assertSession()->pageTextContains('You currently do not have a key');
    $this->assertSession()->elementNotExists('css', '#edit-delete');

    // Test generating a new key.
    $this->drupalPostForm(NULL, [], 'Generate new key');
    $user2 = $this->loadUser($user2->id());
    $this->assertNotEmpty($user2->api_key->value);
    $this->assertSession()->pageTextContains($user2->api_key->value);
    $this->assertSession()->elementExists('css', '#edit-delete');

    // Check that the authentication options are present on the form.
    $this->assertSession()->pageTextContains('Include the following header');
    $this->assertSession()->pageTextContains('Include the following query');

    // Remove one key detection methods.
    $this->keyAuthConfig->set('detection_methods', [
      KeyAuth::DETECTION_METHOD_QUERY,
    ])->save();

    // Check that it was removed.
    $this->drupalGet(Url::fromRoute('key_auth.user_key_auth_form', ['user' => $user2->id()]));
    $this->assertSession()->pageTextNotContains('Include the following header');

    // Try to access other user's form.
    $this->drupalGet(Url::fromRoute('key_auth.user_key_auth_form', ['user' => $user1->id()]));
    $this->assertSession()->statusCodeEquals(403);

    // Create a user with adnin access and log in.
    $user3 = $this->drupalCreateUser(['administer users', 'use key authentication']);
    $this->drupalLogin($user3);

    // Try to access all user forms as admin.
    foreach ([$user1->id(), $user2->id(), $user3->id()] as $uid) {
      $this->drupalGet(Url::fromRoute('key_auth.user_key_auth_form', ['user' => $uid]));
      $this->assertSession()->statusCodeEquals(200);
    }
  }

  /**
   * Test key authentication and related settings.
   */
  public function testKeyAuth() {
    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();

    // Enable both key detection methods.
    $this->keyAuthConfig->set('detection_methods', [
      KeyAuth::DETECTION_METHOD_HEADER,
      KeyAuth::DETECTION_METHOD_QUERY,
    ])->save();

    // Load the parameter name.
    $param_name = $this->keyAuthConfig->get('param_name');

    // Check the test page while not authenticated.
    $this->keyAuthRequest(NULL, NULL, 403);

    // Create a user that can use key authentication.
    $user = $this->drupalCreateUser(['use key authentication']);

    // Assign the user a key.
    $user->set('api_key', $this->keyAuth->generateKey())->save();

    // Test the authentication via query.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_QUERY, $param_name, 200, $user->api_key->value, $user);

    // Test the authentication via header.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_HEADER, $param_name, 200, $user->api_key->value, $user);

    // Test the authentication via query with the wrong key.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_QUERY, $param_name, 403, $this->keyAuth->generateKey(), $user);

    // Test the authentication via header with the wrong key.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_HEADER, $param_name, 403, $this->keyAuth->generateKey(), $user);

    // Disable both detection methods.
    $this->keyAuthConfig->set('detection_methods', [])->save();

    // Test the authentication via query.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_QUERY, $param_name, 403, $user->api_key->value, $user);

    // Test the authentication via header.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_HEADER, $param_name, 403, $user->api_key->value, $user);

    // Re-enable both key detection methods.
    $this->keyAuthConfig->set('detection_methods', [
      KeyAuth::DETECTION_METHOD_HEADER,
      KeyAuth::DETECTION_METHOD_QUERY,
    ])->save();

    // Change the parameter name.
    $this->keyAuthConfig->set('param_name', 'testauth')->save();

    // Test the authentication via query using the new parameter name.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_QUERY, 'testauth', 200, $user->api_key->value, $user);

    // Test the authentication via header using the new parameter name.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_HEADER, 'testauth', 200, $user->api_key->value, $user);

    // Test the authentication via query using the old parameter name.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_QUERY, $param_name, 403, $user->api_key->value, $user);

    // Test the authentication via header using the old parameter name.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_HEADER, $param_name, 403, $user->api_key->value, $user);

    // Create a new user that cannot use key authentication.
    $user = $this->drupalCreateUser([]);

    // Assign the user a key.
    $user->set('api_key', $this->keyAuth->generateKey())->save();

    // Test the authentication via query.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_QUERY, 'testauth', 403, $user->api_key->value, $user);

    // Test the authentication via header.
    $this->keyAuthRequest(KeyAuth::DETECTION_METHOD_HEADER, 'testauth', 403, $user->api_key->value, $user);
  }

  /**
   * Perform a key authentication request to the test page.
   *
   * @param string $detection_method
   *   The key detection method.
   * @param string $param_name
   *   The key parameter name.
   * @param int $status_code
   *   The expected response status code.
   * @param string $key
   *   The authentication key.
   * @param \Drupal\user\UserInterface $user
   *   The user making the request.
   */
  public function keyAuthRequest($detection_method = NULL, $param_name = NULL, $status_code = 200, $key = NULL, UserInterface $user = NULL) {
    // Check if no key or detection method was provided.
    if (!$detection_method || !$key) {
      // Make a regular request.
      $this->drupalGet(Url::fromRoute('key_auth.test'));
    }
    // Header detection.
    elseif ($detection_method == KeyAuth::DETECTION_METHOD_HEADER) {
      $this->drupalGet(Url::fromRoute('key_auth.test'), [], [$param_name => $key]);
    }
    // Query detection.
    elseif ($detection_method == KeyAuth::DETECTION_METHOD_QUERY) {
      $this->drupalGet(Url::fromRoute('key_auth.test', [], ['query' => [$param_name => $key]]));
    }
    // Bad request.
    else {
      $this->assertTrue(FALSE);
      return;
    }

    // Check the status code.
    $this->assertSession()->statusCodeEquals($status_code);

    // Check if a 200 status code is expected.
    if ($status_code == 200) {
      // Ensure that caching was disabled.
      $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));
      $this->assertIdentical(strpos($this->drupalGetHeader('Cache-Control'), 'public'), FALSE);
    }

    // Check if a user was provided.
    if ($user) {
      // If a 200 is expected, the user's name should appear on the page.
      if ($status_code == 200) {
        $this->assertSession()->pageTextContains($user->getUsername());
      }
      else {
        $this->assertSession()->pageTextNotContains($user->getUsername());
      }
    }

    // Reset the sessions.
    $this->mink->resetSessions();
  }

  /**
   * Load a user entity while bypassing the static cache.
   *
   * @param int $uid
   *   The user entity ID.
   *
   * @return \Drupa\user\UserInterface|null
   *   A user entity, if found, otherwise NULL.
   */
  public function loadUser($uid) {
    $this->userStorage->resetCache([$uid]);
    return $this->userStorage->load($uid);
  }

}
