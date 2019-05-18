<?php

namespace Drupal\Tests\mass_contact\Functional\Form;

use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\Tests\mass_contact\Functional\MassContactTestBase;
use Drupal\user\Entity\Role;
use Drupal\mass_contact\MassContactInterface;

/**
 * Tests for the Mass Contact form.
 *
 * @group mass_contact
 */
class MassContactFormTest extends MassContactTestBase {

  use AssertMailTrait;

  /**
   * Non admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $massContactUser;

  /**
   * The role for changing mass contact permissions.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $massContactRole;

  /**
   * The role to send to.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $recipientRole;

  /**
   * Some test categories.
   *
   * @var \Drupal\mass_contact\Entity\MassContactCategoryInterface[]
   */
  protected $categories;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->massContactUser = $this->createUser(['mass contact send messages', 'mass contact view archived messages']);
    $role_id = $this->massContactUser->getRoles(TRUE);
    $this->massContactRole = Role::load(reset($role_id));

    foreach (range(1, 6) as $i) {
      $this->categories[$i] = $this->createCategory();
    }

    // Add 410 users.
    $this->recipientRole = Role::load($this->createRole([]));
    foreach (range(1, 410) as $i) {
      $account = $this->createUser();
      if ($i == 5) {
        // Block the 5th one.
        $account->block();
      }

      if ($i == 20 || $i == 50) {
        // Opt out of mass contacts for the 20th and 50th users.
        $account->mass_contact_opt_out->value = 1;
      }
      $account->addRole($this->recipientRole->id());
      $account->save();
    }
  }

  /**
   * Tests basic form operation on an unprivileged user.
   */
  public function testNormalAccess() {
    $this->drupalLogin($this->massContactUser);
    // Ensure page loads successfully.
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));
    $this->assertSession()->statusCodeEquals(200);

    // Test with queue system.
    $this->config('mass_contact.settings')->set('send_with_cron', TRUE)->save();

    // Grant permission to one category only.
    $this->massContactRole->grantPermission('mass contact send to users in the ' . $this->categories[2]->id() . ' category')->save();
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('A copy of this message will be archived on this website.');
    $this->assertSession()->pageTextContains('Recipients will be hidden from each other.');
    $this->assertSession()->fieldExists('sender_mail');
    $this->assertSession()->fieldValueEquals('sender_mail', $this->massContactUser->getEmail());
    $this->assertSession()->fieldExists('sender_name');
    $this->assertSession()->fieldValueEquals('sender_name', $this->massContactUser->getDisplayName());

    // Update some options.
    $config = $this->config('mass_contact.settings');
    $config->set('use_bcc', FALSE);
    $config->set('create_archive_copy', FALSE);
    $config->set('default_sender_email', 'foo@bar.com');
    $config->set('default_sender_name', 'Foo Bar');
    // Do not respect opt-outs.
    $config->set('optout_enabled', MassContactInterface::OPT_OUT_DISABLED);
    $config->set('message_prefix', [
      'value' => $this->randomString(),
      'format' => filter_default_format(),
    ]);
    $config->set('message_suffix', [
      'value' => $this->randomString(),
      'format' => filter_default_format(),
    ]);
    $config->save();
    $this->massContactRole->grantPermission('mass contact send to users in the ' . $this->categories[3]->id() . ' category')->save();
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Recipients will NOT be hidden from each other.');
    $this->assertSession()->pageTextContains(' A copy of this message will NOT be archived on this website.');
    $this->assertSession()->fieldNotExists('sender_mail');
    $this->assertSession()->fieldNotExists('sender_name');

    // Set category 2 to send to all authenticated users.
    $recipients = [
      'role' => [
        'conjunction' => 'AND',
        'categories' => [
          $this->recipientRole->id(),
        ],
      ],
    ];
    $this->categories[2]->setRecipients($recipients);
    $this->categories[2]->save();

    // Send a message to category 2.
    $edit = [
      'subject' => $this->randomString(),
      'body[value]' => $this->randomString(),
      'categories[]' => [$this->categories[2]->id()],
    ];
    $this->drupalPostForm(NULL, $edit, t('Send email'));

    $message_configs['optout'] = FALSE;
    $message_configs['copy'] = FALSE;
    $message_configs['bcc'] = FALSE;
    $message_configs['create_archive_copy'] = FALSE;
    $message_configs['user_count'] = 409;
    $this->verifyConfirmFormAndConfirmSendEmail($message_configs);

    /** @var \Drupal\Core\Queue\QueueWorkerManagerInterface $manager */
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $message_queue_queue_worker */
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $send_message_queue_worker */
    $manager = $this->container->get('plugin.manager.queue_worker');
    $message_queue_queue_worker = $manager->createInstance('mass_contact_queue_messages');
    $send_message_queue_worker = $manager->createInstance('mass_contact_send_message');

    // Should be one item in the  Queue messages queue.
    $this->verifyAndProcessQueueMessagesQueue($message_queue_queue_worker, 1);

    // There should now be 9 items in the sending queue and 409 emails
    // (409 non-blocked users with the recipient role).
    // @see \Drupal\mass_contact\MassContact::MAX__QUEUE_RECIPIENTS
    $this->verifyAndProcessSendMessageQueue($send_message_queue_worker, 9, 409);

    // Switch back to BCC mode and only 3 emails should be sent.
    \Drupal::state()->set('system.test_mail_collector', []);
    $config->set('create_archive_copy', TRUE);
    $config->set('use_bcc', TRUE);
    $config->save();
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));

    // Send a message to category 2.
    $edit = [
      'subject' => $this->randomString(),
      'body[value]' => $this->randomString(),
      'categories[]' => [$this->categories[2]->id()],
    ];
    $this->drupalPostForm(NULL, $edit, t('Send email'));

    $message_configs['optout'] = FALSE;
    $message_configs['copy'] = FALSE;
    $message_configs['bcc'] = TRUE;
    $message_configs['create_archive_copy'] = TRUE;
    $message_configs['user_count'] = 409;
    $this->verifyConfirmFormAndConfirmSendEmail($message_configs);

    $this->assertSession()->pageTextContains(t('A copy has been archived'));
    $this->clickLink('here');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));

    // Should be one item in the  Queue messages queue.
    $this->verifyAndProcessQueueMessagesQueue($message_queue_queue_worker, 1);

    // There should now be 9 items in the sending queue and 9 emails
    // (since BCC is used).
    // @see \Drupal\mass_contact\MassContact::MAX__QUEUE_RECIPIENTS
    $this->verifyAndProcessSendMessageQueue($send_message_queue_worker, 9, 9);

    // Verify message prefix/suffix are properly attached.
    $expected = implode("\n\n", [
      $config->get('message_prefix.value'),
      $edit['body[value]'],
      $config->get('message_suffix.value'),
    ]) . "\n\n";
    $this->assertMail('body', $expected);
    $this->assertMail('to', 'foo@bar.com');

    // Test opt out feature.
    \Drupal::state()->set('system.test_mail_collector', []);
    $config->set('optout_enabled', MassContactInterface::OPT_OUT_GLOBAL);
    $config->set('use_bcc', FALSE);
    $config->save();
    // Get form again.
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));
    $this->assertSession()->statusCodeEquals(200);
    // Send a message to category 2 with BCC disabled.
    $edit = [
      'subject' => $this->randomString(),
      'body[value]' => $this->randomString(),
      'categories[]' => [$this->categories[2]->id()],
    ];

    $this->drupalPostForm(NULL, $edit, t('Send email'));

    $message_configs['optout'] = TRUE;
    $message_configs['copy'] = FALSE;
    $message_configs['bcc'] = FALSE;
    $message_configs['create_archive_copy'] = TRUE;
    $message_configs['user_count'] = 407;
    $this->verifyConfirmFormAndConfirmSendEmail($message_configs);

    // Should be one item in the  Queue messages queue.
    $this->verifyAndProcessQueueMessagesQueue($message_queue_queue_worker, 1);

    // There should now be 9 items in the sending queue and 407 emails
    // (since BCC is not used and 2 users have opted out).
    // @see \Drupal\mass_contact\MassContact::MAX__QUEUE_RECIPIENTS
    $this->verifyAndProcessSendMessageQueue($send_message_queue_worker, 9, 407);

    // Test send me a copy feature.
    \Drupal::state()->set('system.test_mail_collector', []);
    // Use BCC for this test.
    $config->set('use_bcc', TRUE);
    $config->save();
    // Get form again.
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));
    $this->assertSession()->statusCodeEquals(200);
    // Test Send a message without any categories with 'Send me a copy'
    // unchecked. Mail should not be sent since there are no recipients.
    $edit = [
      'subject' => $this->randomString(),
      'body[value]' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Send email'));
    $this->assertSession()->pageTextContains('There are no recipients chosen for this mass contact message.');

    // Test Sending a message without any categories with
    // 'Send me a copy checked'. Mail should be sent since there is one
    // recipient.
    $edit = [
      'subject' => $this->randomString(),
      'body[value]' => $this->randomString(),
      'copy' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Send email'));

    $message_configs['optout'] = TRUE;
    $message_configs['copy'] = TRUE;
    $message_configs['bcc'] = TRUE;
    $message_configs['create_archive_copy'] = TRUE;
    $message_configs['user_count'] = 1;
    $this->verifyConfirmFormAndConfirmSendEmail($message_configs);

    // Should be one item in the  Queue messages queue.
    $this->verifyAndProcessQueueMessagesQueue($message_queue_queue_worker, 1);

    // There should now be only 1 item in the sending queue for the current
    // user and 1 email sent.
    $this->verifyAndProcessSendMessageQueue($send_message_queue_worker, 1, 1);

    // Test sending a message to category 2 and also a copy to yourself with
    // BCC option as false.
    $config->set('use_bcc', FALSE);
    $config->save();
    // Get form again.
    $this->drupalGet(Url::fromRoute('entity.mass_contact_message.add_form'));
    \Drupal::state()->set('system.test_mail_collector', []);

    $edit = [
      'subject' => $this->randomString(),
      'body[value]' => $this->randomString(),
      'categories[]' => [$this->categories[2]->id()],
      'copy' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Send email'));

    $message_configs['optout'] = TRUE;
    $message_configs['copy'] = TRUE;
    $message_configs['bcc'] = FALSE;
    $message_configs['create_archive_copy'] = TRUE;
    $message_configs['user_count'] = 408;
    $this->verifyConfirmFormAndConfirmSendEmail($message_configs);

    // Should be one item in the  Queue messages queue.
    $this->verifyAndProcessQueueMessagesQueue($message_queue_queue_worker, 1);

    // There should now be 9 items in the sending queue for the current
    // user and should be 408 emails (407 non-blocked users and non opted out
    // users with the recipient role and 1 current user for copy).
    $this->verifyAndProcessSendMessageQueue($send_message_queue_worker, 9, 408);

    // Test cancelling the message confirm form.
    // No messages should be sent if the cancel button is pressed on the
    // confirmation form.
    \Drupal::state()->set('system.test_mail_collector', []);
    $edit = [
      'subject' => $this->randomString(),
      'body[value]' => $this->randomString(),
      'categories[]' => [$this->categories[2]->id()],
      'copy' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Send email'));
    $message_configs['optout'] = TRUE;
    $message_configs['copy'] = TRUE;
    $message_configs['bcc'] = FALSE;
    $message_configs['create_archive_copy'] = TRUE;
    $message_configs['user_count'] = 408;
    $message_configs['cancel'] = TRUE;
    $this->verifyConfirmFormAndConfirmSendEmail($message_configs);

    $this->verifyAndProcessQueueMessagesQueue($message_queue_queue_worker, 0);

    // @todo Test with batch system.
    // @see https://www.drupal.org/node/2855942
    $this->config('mass_contact.settings')->set('send_with_cron', FALSE)->save();
    \Drupal::state()->set('system.test_mail_collector', []);
  }

  /**
   * Verifies the number of items in the mass_contact_queue_messages queue.
   *
   * Also processes the queue.
   *
   * @param \Drupal\Core\Queue\QueueWorkerInterface $queue_worker
   *   The queue worker for the mass_contact_queue_messages queue.
   * @param int $expected_queue_items
   *   Number of items expected in the mass_contact_queue_messages queue.
   */
  protected function verifyAndProcessQueueMessagesQueue(QueueWorkerInterface $queue_worker, $expected_queue_items) {
    $queue = \Drupal::queue('mass_contact_queue_messages');
    // Number of items in the queue_messages queue should be equal to
    // $expected_queue_items.
    $this->assertEquals($expected_queue_items, $queue->numberOfItems());

    // Process the queue.
    while ($item = $queue->claimItem()) {
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }
  }

  /**
   * Verifies the number of items in the mass_contact_send_message queue.
   *
   * Also processes the queue and verifies the number of emails generated.
   *
   * @param \Drupal\Core\Queue\QueueWorkerInterface $queue_worker
   *   The queue worker for the mass_contact_send_message queue.
   * @param int $expected_queue_items
   *   Number of items expected in the mass_contact_send_message queue.
   * @param int $expected_mails
   *   Number of emails expected to be sent.
   */
  protected function verifyAndProcessSendMessageQueue(QueueWorkerInterface $queue_worker, $expected_queue_items, $expected_mails) {
    $queue = \Drupal::queue('mass_contact_send_message');
    // Number of items in the send_messages queue should be equal to
    // $expected_queue_items.
    $this->assertEquals($expected_queue_items, $queue->numberOfItems());

    // Process the queue.
    while ($item = $queue->claimItem()) {
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
    }

    // Number of emails should be equal to $expected_mails.
    $emails = $this->getMails();
    $this->assertEquals($expected_mails, count($emails));
  }

  /**
   * Verifies text in the confirmation form and submits the confirmation form.
   *
   * Form submission is cancelled if configs have an entry for
   * $message_configs['cancel'].
   *
   * @param array $message_configs
   *   An array of chosen message configs which decides the text in the
   *   confirmation form.
   */
  protected function verifyConfirmFormAndConfirmSendEmail(array $message_configs) {
    $this->assertSession()
      ->pageTextContains('Are you sure you want to send this message to ' . $message_configs['user_count'] . ' user(s)?');
    if ($this->massContactUser->hasPermission('mass contact administer')) {
      if ($message_configs['optout']) {
        $this->assertSession()
          ->pageTextContains('You have selected to respect user opt-outs. If a user has opted out of emails they will not receive this mass contact message.');
      }
      else {
        $this->assertSession()
          ->pageTextContains('You have selected to NOT respect user opt-outs. Emails will be sent to all users even if they have elected not to receive a mass contact message.');
      }
    }
    if ($this->massContactUser->hasPermission('mass contact override bcc')) {
      if ($message_configs['bcc']) {
        $this->assertSession()
          ->pageTextContains('Recipients of this message will be HIDDEN on the email');
      }
      else {
        $this->assertSession()
          ->pageTextContains('Recipients of this message will NOT be HIDDEN on the email');
      }
    }
    if ($message_configs['copy']) {
      $this->assertSession()
        ->pageTextContains('A copy of this message will be sent to you.');
    }
    else {
      $this->assertSession()
        ->pageTextContains('A copy of this message will NOT be sent to you.');
    }
    if ($this->massContactUser->hasPermission('mass contact override archiving')) {
      if ($message_configs['create_archive_copy']) {
        $this->assertSession()
          ->pageTextContains('A copy of this message will be archived on this site.');
      }
      else {
        $this->assertSession()
          ->pageTextContains('A copy of this message will NOT be archived on this site.');
      }
    }
    if (empty($message_configs['cancel'])) {
      $this->drupalPostForm(NULL, [], t('Confirm'));
      $this->assertSession()
        ->pageTextContains('Mass Contact message sent successfully.');
    }
    else {
      $this->clickLink('Cancel');
    }
  }

}
