<?php

namespace Drupal\Tests\welcome_mail\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the JavaScript functionality of the Welcome mail module.
 *
 * @group welcome_mail
 */
class MailQueuedTest extends JavascriptTestBase {

  public static $modules = [
    'welcome_mail',
  ];

  /**
   * Tests that a user is queued.
   */
  public function testQueue() {
    // Create a user.
    $this->drupalCreateUser();
    $queue = \Drupal::queue(WELCOME_MAIL_QUEUE_NAME);
    // Should not trigger a queue item, since we have not enabled anything yet.
    $this->assertEquals(0, $queue->numberOfItems());
    // Enable the welcome mail.
    $this->config('welcome_mail.settings')
      ->set('enabled', TRUE)
      ->save();
    // Create a user.
    $this->drupalCreateUser();
    $queue = \Drupal::queue(WELCOME_MAIL_QUEUE_NAME);
    // Should now hold an item.
    $this->assertEquals(1, $queue->numberOfItems());
  }

}
