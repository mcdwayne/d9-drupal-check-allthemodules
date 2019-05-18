<?php

namespace Drupal\Tests\session_based_temp_store\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Generic Behavior Tests for the SessionBasedTempStore.
 *
 * @group session_based_temp_store
 */
class SessionBasedTempStoreServiceTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['session_based_temp_store'];

  /**
   * User interaction tests for SessionBasedTempStore.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testGenericBehavior() {
    // Let's create data in the storage for anonymous user.
    // And check the storage works for one.
    $temp_store = $this->container->get('session_based_temp_store')
      ->get('private_temp_store', 4800);
    $temp_store->set('foo-1', 'bar-1');
    $metadata1 = $temp_store->getMetadata('foo-1');
    $this->assertEquals('bar-1', $temp_store->get('foo-1'));
    $this->assertNotEmpty($metadata1->owner);

    // Check that cookie session doesn't exist to avoid Varnish destruction.
    $this->sessionCookieDoesNotExist();

    // Let's imagine anonymous gets authenticated.
    $authenticated = $this->drupalCreateUser();
    $this->drupalLogin($authenticated);

    // Check that the user's session really exists.
    $assert = $this->assertSession();
    $session_name = $this->getSessionName();
    $assert->cookieExists($session_name);

    // Check that user still has access to the saved data as anonymous.
    $this->assertEquals('bar-1', $temp_store->get('foo-1'));

    // Then check the storage works for the authenticated.
    $temp_store->set('foo-2', 'bar-2');
    $metadata2 = $temp_store->getMetadata('foo-2');
    $this->assertEquals('bar-2', $temp_store->get('foo-2'));
    $this->assertNotEmpty($metadata2->owner);

    // The owner has to remain the same.
    $this->assertEquals($metadata2->owner, $metadata1->owner);
  }

  /**
   * Checks that session cookie doesn't exist.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function sessionCookieDoesNotExist() {
    $name = $this->getSessionName();
    $message = sprintf('Cookie session "%s" exists, but should not be.', $name);
    $this->assertSession()->assert($this->getSession()
      ->getCookie($name) === NULL, $message);
  }

}
