<?php

namespace Drupal\Tests\log_entity_login_attempts\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test basic event log.
 *
 * @group log_entity
 */
class LoginAttemptEventTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'log_entity',
    'log_entity_login_attempts',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $storage = $this->container->get('entity_type.manager')->getStorage('log_entity');
    $all = $storage->loadMultiple();
    $storage->delete($all);
  }

  /**
   * Test that events are logged upon login.
   */
  public function testSuccessfulLoginEvent() {
    $account = $this->createUser();
    $this->drupalLogin($account);
    $this->assertLogEvent('login_attempt', sprintf('User %s successfully logged in', $account->getAccountName()));
  }

  /**
   * Test that events are logged upon login.
   */
  public function testFailedLoginEvent() {
    $account = $this->createUser();
    $this->submitForm(array(
      'name' => $account->getUsername(),
      'pass' => 'wrong password',
    ), t('Log in'));
    $this->assertLogEvent('login_attempt', sprintf('User %s failed to login', $account->getAccountName()));
  }

  /**
   * Assert the correct event was logged.
   *
   * @param string $type
   *   The event type.
   * @param string $description
   *   The event description.
   */
  protected function assertLogEvent($type, $description) {
    $events = array_values($this->container->get('entity_type.manager')->getStorage('log_entity')->loadMultiple());
    $this->assertCount(1, $events);
    $this->assertEquals($type, $events[0]->getEventType());
    $this->assertStringStartsWith($description, $events[0]->getDescription());
  }

}
