<?php

namespace Drupal\Tests\private_message\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\private_message\Entity\PrivateMessage;

/**
 * Tests notification emails when a new private message is created.
 *
 * @package Drupal\Tests\private_message\Kernel
 * @group private_message
 */
class UserNotificationEmailTest extends EntityKernelTestBase {

  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'private_message',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The thread manager service.
   *
   * @var \Drupal\private_message\Service\PrivateMessageThreadManagerInterface
   */
  private $threadManager;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  private $userData;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('pm_thread_access_time');
    $this->installEntitySchema('pm_thread_delete_time');
    $this->installEntitySchema('private_message');
    $this->installEntitySchema('private_message_thread');
    $this->installEntitySchema('user');

    $this->installSchema('user', ['users_data']);

    $this->installConfig(['private_message']);

    $this->threadManager = \Drupal::service('private_message.thread_manager');
    $this->userData = \Drupal::service('user.data');
  }

  /**
   * Test that notification emails are sent when a private message is created.
   */
  public function testANotificationEmailIsSent() {
    $settings = \Drupal::config('private_message.settings')->getRawData();
    $this->assertTrue($settings['enable_email_notifications']);
    $this->assertTrue($settings['send_by_default']);

    $owner = $this->createUser();
    $member1 = $this->createUser(['mail' => 'member1@example.com']);

    \Drupal::currentUser()->setAccount($owner);

    $message = $this->createMessage(['owner' => $owner]);
    $this->threadManager->saveThread($message, [$owner, $member1]);

    $mails = $this->getMails();

    // There should only be one email sent, as the current user should not
    // receive an email.
    $this->assertCount(1, $mails);

    // Assert that the correct email was sent, and to the right e-mail address.
    $this->assertEquals('private_message_message_notification', $mails[0]['id']);
    $this->assertEquals('member1@example.com', $mails[0]['to']);
  }

  /**
   * Test that notification emails can be disabled globally.
   */
  function testNotificationEmailsCanBeDisabled() {
    $settings = \Drupal::configFactory()->getEditable('private_message.settings');
    $settings->set('enable_email_notifications', FALSE);
    $settings->save(TRUE);

    $this->assertFalse($settings->get('enable_email_notifications'));

    $user1 = $this->createUser();
    $user2 = $this->createUser();

    $message = $this->createMessage();

    $this->threadManager->saveThread($message, [$user1, $user2]);

    $this->assertCount(0, $this->getMails());
  }

  /**
   * Test that users who have disabled notifications do not get an email.
   */
  public function testAUserCanDisableEmailNotifications() {
    $owner = $this->createUser();
    $member1 = $this->createUser(['mail' => 'member1@example.com']);
    $member2 = $this->createUser(['mail' => 'member2@example.com']);

    \Drupal::currentUser()->setAccount($owner);

    $this->disableNotificationsForUser($member2);

    $message = $this->createMessage(['owner' => $owner]);
    $this->threadManager->saveThread($message, [$owner, $member1, $member2]);

    $this->assertCount(1, $this->getMails());
  }

  /**
   * Create a new private message with some default values.
   *
   * @param array $values
   *   An array of values to set on the message.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The new private message.
   */
  private function createMessage(array $values = []) {
    $message = PrivateMessage::create(array_merge([
      'message' => $this->randomString(),
    ], $values));

    $message->save();

    return $message;
  }

  /**
   * Disable email notifications for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *  The user account.
   */
  private function disableNotificationsForUser(AccountInterface $user) {
    $this->userData->set('private_message', $user->id(), 'email_notification', FALSE);

    $this->assertFalse($this->userData->get('private_message', $user->id(), 'email_notification'));
  }

}
