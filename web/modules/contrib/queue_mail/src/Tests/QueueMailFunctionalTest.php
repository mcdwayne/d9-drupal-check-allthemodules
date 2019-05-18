<?php

namespace Drupal\queue_mail\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests queue mail functionality.
 *
 * @group queue_mail
 */
class QueueMailFunctionalTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Queuing mail',
      'description' => 'Test queuing emails using Queue Mail.',
      'group' => 'Mail',
    );
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('queue_mail', 'queue_mail_test');

  /**
   * Test that if we're not queuing any emails that they get sent as normal.
   */
  function testNonQueuedEmail() {
    // Send an email and ensure it was sent immediately.
    \Drupal::configFactory()->getEditable('queue_mail.settings')
      ->set('queue_mail_keys', '')
      ->save();
    $this->sendEmailAndTest('basic', FALSE);
  }

  /**
   * Test that if we are queuing emails, that they get queued.
   */
  function testQueuedEmail() {
    // Set all emails to be queued and test.
    \Drupal::configFactory()->getEditable('queue_mail.settings')
      ->set('queue_mail_keys', '*')
      ->save();
    // Set a specific MailSystem for the email we want to send.
    /*\Drupal::configFactory()->getEditable('queue_mail.settings')
      ->set('interface.queue_mail_test', \Drupal::config('system.mail')->get('interface.default'))
      ->save();*/
    $this->sendEmailAndTest();
  }

  /**
   * This tests the matching of mailkeys to be queued.
   *
   * For example, we test that a specific email from a module is queued, and
   * that emails from another module are not queued.
   */
  function testQueuedEmailKeyMatching() {
    // Set only some emails to be queued and test.
    \Drupal::configFactory()->getEditable('queue_mail.settings')
      ->set('queue_mail_keys', 'queue_mail_test_queued')
      ->save();
    $this->sendEmailAndTest('queued', TRUE);
    $this->sendEmailAndTest('not_queued', FALSE);

    // And test the wildcard matching.
    \Drupal::configFactory()->getEditable('queue_mail.settings')
      ->set('queue_mail_keys', 'queue_mail_test_que*')
      ->save();
    $this->sendEmailAndTest('queued', TRUE);
    $this->sendEmailAndTest('not_queued', FALSE);
  }

  /**
   * Send an email and ensure it is queued or sent immediately.
   *
   * @param $mail_key
   *   The key of the email to send.
   * @param $should_be_queued
   *   Pass in TRUE to test if the email was queued, FALSE to test that it
   *   wasn't queued.
   */
  function sendEmailAndTest($mail_key = 'basic', $should_be_queued = TRUE) {
    $queue = _queue_mail_get_queue();
    // Parameters before testing.
    $queue_count_before = $queue->numberOfItems();
    $email_count_before = count($this->drupalGetMails());
    $content = $this->randomMachineName();

    // Send test email.
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    \Drupal::service('plugin.manager.mail')->mail('queue_mail_test',
      $mail_key, 'info@example.com', $langcode, array('content' => $content));

    $queue_count_after = $queue->numberOfItems();
    $email_count_after = count($this->drupalGetMails());

    // Now do the desired assertions.
    if ($should_be_queued === TRUE) {
      $this->assertTrue($queue_count_after - $queue_count_before == 1, 'Email is queued.');
      $this->assertTrue($email_count_after - $email_count_before == 0, 'Queued email is not sent immediately.');

      // Now run cron and see if our email gets sent.
      $queue_count_before = $queue->numberOfItems();
      $email_count_before = count($this->drupalGetMails());
      $this->cronRun();
      $this->assertMailString('body', $content, 1);
      $queue_count_after = $queue->numberOfItems();
      $email_count_after = count($this->drupalGetMails());
      $this->assertTrue($queue_count_after - $queue_count_before == -1, 'Email is sent from the queue.');
      $this->assertTrue($email_count_after - $email_count_before == 1, 'Queued email is sent on cron.');
    }
    elseif ($should_be_queued === FALSE) {
      $this->assertTrue($queue_count_after - $queue_count_before == 0, 'Email is not queued.');
      $this->assertTrue($email_count_after - $email_count_before == 1, 'Email is sent immediately.');
      $this->assertMailString('body', $content, 1);
    }
  }
}
