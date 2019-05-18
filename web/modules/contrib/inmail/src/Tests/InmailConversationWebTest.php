<?php

namespace Drupal\inmail\Tests;

/**
 * Tests the 'Email display' of the Conversation case.
 *
 * @group inmail
 * @requires module past_db
 */
class InmailConversationWebTest extends InmailWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'inmail_mailmute',
    'field_ui',
    'past_db',
    'past_testhidden',
    'inmail_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Make sure new users are blocked until approved by admin.
    \Drupal::configFactory()->getEditable('user.settings')
      ->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)
      ->save();
    // Enable logging of raw mail messages.
    \Drupal::configFactory()->getEditable('inmail.settings')
      ->set('log_raw_emails', TRUE)
      ->save();
  }

  /**
   * Tests Conversations Email Display threads.
   *
   * Conversation's thread tree (newest sub-thread first):
   * 00 1479975011@mail.gmail.com, Original conversation message
   * 02 - 1479975357@mail.gmail.com, Re: Original conversation message
   * 03 -- 1479975484@mail.gmail.com, Re: Re: Original conversation message
   * 05 --- 1479984876@mail.gmail.com, Re: Re: Re: Original conversation message
   * 06 --- 1479984891@mail.gmail.com, Fw: Re: Re: Original conversation message
   * 01 - 1479975222@mail.gmail.com, Re: Original conversation message
   * 04 -- 1479975856@mail.gmail.com, Re: Re: Original conversation message
   */
  public function testConversationsEmailDisplay() {
    // Alice sends the original email conversation message to Bob and Caroline.
    $raw_original = $this->getMessageFileContents('conversations/original.eml');
    $this->processRawMessage($raw_original);
    $event_original = $this->getLastEventByMachinename('process');
    // Assert identification and address header fields in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event_original->id() . '/full');
    $this->assertIdentificationField('Message-ID', '<1479975011@mail.gmail.com>');
    $this->assertAddressHeaderField('From', 'alice@example.com', 'Alice');
    $this->assertAddressHeaderField('To', 'bob@example.com', 'Bob', 1);
    $this->assertAddressHeaderField('To', 'caroline@example.com', 'Caroline', 2);
    $this->assertElementHeaderField('Subject', 'Original conversation message');

    // Bob replies to the original conversation message to Alice only.
    $raw_first_reply = $this->getMessageFileContents('conversations/tree-01--1-reply.eml');
    $this->processRawMessage($raw_first_reply);
    $event_first = $this->getLastEventByMachinename('process');
    // Assert identification and address header fields in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event_first->id() . '/full');
    $this->assertIdentificationField('Message-ID', '<1479975222@mail.gmail.com>');
    $this->assertIdentificationField('In-Reply-To', '<1479975011@mail.gmail.com>');
    $this->assertNoIdentificationField('References');
    $this->assertAddressHeaderField('From', 'bob@example.com', 'Bob');
    $this->assertAddressHeaderField('reply to', 'bob_reply_to@example.com', 'Bob');
    $this->assertAddressHeaderField('To', 'alice@example.com', 'Alice');
    $this->assertElementHeaderField('Subject', 'Re: Original conversation message');

    // Caroline replies to the original conversation message to Alice only.
    $raw_second_reply = $this->getMessageFileContents('conversations/tree-02--2-reply.eml');
    $this->processRawMessage($raw_second_reply);
    $event_second = $this->getLastEventByMachinename('process');
    // Assert identification and address header fields in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event_second->id() . '/full');
    $this->assertIdentificationField('Message-ID', '<1479975357@mail.gmail.com>');
    $this->assertNoIdentificationField('In-Reply-To');
    $this->assertIdentificationField('References', '<1479975011@mail.gmail.com>');
    $this->assertAddressHeaderField('From', 'caroline@example.com', 'Caroline');
    // Identical 'Reply-To' as 'From' mailbox should not be displayed.
    $this->assertNoAddressHeaderField('reply to', 'caroline@example.com', 'Caroline');
    $this->assertAddressHeaderField('To', 'alice@example.com', 'Alice');
    $this->assertElementHeaderField('Subject', 'Re: Original conversation message');

    // Alice replies to Caroline's reply (second-reply) to Caroline only.
    $raw_third_reply = $this->getMessageFileContents('conversations/tree-03--2-1-reply.eml');
    $this->processRawMessage($raw_third_reply);
    $event_third = $this->getLastEventByMachinename('process');
    // Assert identification and address header fields in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event_third->id() . '/full');
    $this->assertIdentificationField('Message-ID', '<1479975484@mail.gmail.com>');
    $this->assertIdentificationField('In-Reply-To', '<1479975357@mail.gmail.com>');
    $this->assertIdentificationField('References', '<1479975011@mail.gmail.com> <1479975357@mail.gmail.com>');
    $this->assertAddressHeaderField('From', 'alice@example.com', 'Alice');
    $this->assertAddressHeaderField('To', 'caroline@example.com', 'Caroline');
    $this->assertElementHeaderField('Subject', 'Re: Re: Original conversation message');

    // Alice replies to Bob's reply message (first-reply) to Bob only.
    $raw_fourth_reply = $this->getMessageFileContents('conversations/tree-04--1-1-reply.eml');
    $this->processRawMessage($raw_fourth_reply);
    $event_fourth = $this->getLastEventByMachinename('process');
    // Assert identification and address header fields in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event_fourth->id() . '/full');
    $this->assertIdentificationField('Message-ID', '<1479975856@mail.gmail.com>');
    $this->assertIdentificationField('In-Reply-To', '<1479975222@mail.gmail.com>');
    $this->assertIdentificationField('References', '<1479975011@mail.gmail.com> <1479975222@mail.gmail.com>');
    $this->assertAddressHeaderField('From', 'alice@example.com', 'Alice');
    $this->assertAddressHeaderField('To', 'bob_reply_to@example.com', 'Bob');
    $this->assertElementHeaderField('Subject', 'Re: Re: Original conversation message');

    // Caroline replies to Alice's reply message (third-reply) to Alice only.
    $raw_fifth_reply = $this->getMessageFileContents('conversations/tree-05--2-1-1-reply.eml');
    $this->processRawMessage($raw_fifth_reply);
    $event_fifth = $this->getLastEventByMachinename('process');
    // Assert identification and address header fields in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event_fifth->id() . '/full');
    $this->assertIdentificationField('Message-ID', '<1479984876@mail.gmail.com>');
    $this->assertNoIdentificationField('In-Reply-To');
    $this->assertIdentificationField('References', '<1479975011@mail.gmail.com> <1479975357@mail.gmail.com> <1479975484@mail.gmail.com>');
    $this->assertAddressHeaderField('From', 'caroline@example.com', 'Caroline');
    // Identical 'Reply-To' as 'From' mailbox should not be displayed.
    $this->assertNoAddressHeaderField('reply to', 'caroline@example.com', 'Caroline');
    $this->assertAddressHeaderField('To', 'alice@example.com', 'Alice');
    $this->assertElementHeaderField('Subject', 'Re: Re: Re: Original conversation message');

    // Alice forwards her reply message (third-reply) to Bob.
    $raw_sixth_reply = $this->getMessageFileContents('conversations/tree-06--2-1-2-forward.eml');
    $this->processRawMessage($raw_sixth_reply);
    $event_sixth = $this->getLastEventByMachinename('process');
    // Assert identification and address header fields in 'full' view mode.
    $this->drupalGet('admin/inmail-test/email/' . $event_sixth->id() . '/full');
    $this->assertIdentificationField('Message-ID', '<1479984891@mail.gmail.com>');
    $this->assertIdentificationField('In-Reply-To', '<1479975484@mail.gmail.com>');
    $this->assertIdentificationField('References', '<1479975011@mail.gmail.com> <1479975357@mail.gmail.com> <1479975484@mail.gmail.com>');
    $this->assertAddressHeaderField('From', 'alice@example.com', 'Alice');
    $this->assertAddressHeaderField('To', 'bob@example.com', 'Bob');
    $this->assertAddressHeaderField('CC', 'caroline@example.com', 'Caroline');
    $this->assertElementHeaderField('Subject', 'Fw: Re: Re: Original conversation message');
  }

}
