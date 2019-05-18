<?php
/**
 * @file
 * Contains \Drupal\Tests\mailmute_simplenews\Kernel\MailmuteSimplenewsTest.
 */

namespace Drupal\Tests\mailmute_simplenews\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\mailmute\Kernel\MailmuteKernelTestBase;
use Drupal\simplenews\Entity\Subscriber;
use Drupal\simplenews\Mail\MailTest;

/**
 * Tests send state for the Simplenews Subscriber entity.
 *
 * @group mailmute
 *
 * @requires module simplenews
 */
class MailmuteSimplenewsTest extends MailmuteKernelTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array(
    'simplenews',
    'field',
    'mailmute',
    'mailmute_simplenews',
    'user',
    'system',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('simplenews_subscriber');
    $this->installConfig(['mailmute', 'mailmute_simplenews', 'system']);
    \Drupal::configFactory()->getEditable('system.site')
      ->set('mail', 'admin@example.com')
      ->save();
  }

  /**
   * Tests send states for the Subscriber entity.
   */
  public function testStates() {
    // A Send state field should be added to Subscriber on install.
    $field_map = \Drupal::entityManager()->getFieldMap();
    $this->assertEqual($field_map['simplenews_subscriber']['sendstate']['type'], 'sendstate');

    $name = $this->randomMachineName();
    /** @var \Drupal\simplenews\Entity\Subscriber $subscriber */
    $subscriber = Subscriber::create(array(
      'mail' => "$name@example.com",
    ));

    // Default plugin_id should be send.
    $this->assertEqual($subscriber->sendstate->plugin_id, 'send');

    // Mails should be sent normally.
    $sent = $this->mailSimplenewsTestTo($subscriber);
    $this->assertTrue($sent, 'Mail is sent normally.');

    // When plugin_id is onhold, mails should not be sent.
    $subscriber->sendstate->plugin_id = 'onhold';
    $subscriber->save();
    $sent = $this->mailSimplenewsTestTo($subscriber);
    $this->assertFalse($sent, 'Mail is suppressed.');
  }

  /**
   * Attempts to send a Simplenews test mail, and indicates success.
   *
   * @param \Drupal\simplenews\SubscriberInterface $subscriber
   *   Subscriber object to send email to.
   *
   * @return bool
   *   The result status.
   */
  protected function mailSimplenewsTestTo($subscriber) {
    $params = array('simplenews_mail' => new MailTest('plain'));
    $message = $this->mailManager->mail('simplenews', 'test', $subscriber->getMail(), LanguageInterface::LANGCODE_DEFAULT, $params);
    return $message['result'];
  }

}
