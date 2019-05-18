<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\inmail\MIME\MimeHeader;
use Drupal\inmail\MIME\MimeHeaderField;
use Drupal\inmail\MIME\MimeMessage;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MimeMessage class.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\MimeMessage
 *
 * @group inmail
 */
class MimeMessageTest extends UnitTestCase {

  /**
   * Tests the message ID getter.
   *
   * @covers ::getMessageId
   */
  public function testGetMessageId() {
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('Message-ID', '<Foo@example.com>')]), 'Bar');
    $this->assertEquals('<Foo@example.com>', $message->getMessageId());
  }

  /**
   * Tests the References getter.
   *
   * @covers ::getReferences
   */
  public function testGetReferences() {
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('References', '')]), 'Foobar');
    $this->assertNull($message->getReferences());

    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField(
        'References',
        '<parent-references@example.com> <parent-msg-id@example.com>'
      )]), 'Bar');
    $references = $message->getReferences();
    $this->assertEquals('<parent-references@example.com>', $references[0]);
    $this->assertEquals('<parent-msg-id@example.com>', $references[1]);
  }

  /**
   * Tests the In-Reply-To getter.
   *
   * @covers ::getInReplyTo
   */
  public function testGetInReplyTo() {
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('In-Reply-To', '')]), 'Foobar');
    $this->assertNull($message->getInReplyTo());

    // Usually real mail client examples provide just one identifier.
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('In-Reply-To', '<parent-in-reply-to@example.com>')]), 'Foo');
    $this->assertEquals('<parent-in-reply-to@example.com>', $message->getInReplyTo()[0]);

    // According to RFC, In-Reply-To could have multiple parent's msg-id.
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField(
        'In-Reply-To',
        '<grandparent-msg-id@example.com> <parent-msg-id@example.com>'
      )]), 'Bar');
    $in_reply_to = $message->getInReplyTo();
    $this->assertEquals('<grandparent-msg-id@example.com>', $in_reply_to[0]);
    $this->assertEquals('<parent-msg-id@example.com>', $in_reply_to[1]);
  }

  /**
   * Tests the subject getter.
   *
   * @covers ::getSubject
   */
  public function testGetSubject() {
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('Subject', 'Foo')]), 'Bar');
    $this->assertEquals('Foo', $message->getSubject());
  }

  /**
   * Tests the sender getter.
   *
   * @covers ::getFrom
   */
  public function testGetFrom() {
    // Single address.
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('From', 'foo@example.com')
    ]), 'Bar');
    $this->assertEquals('foo@example.com', $message->getFrom()[0]->getAddress());

    // According to RFC 2822, From field consists of one or more coma separated
    // list of mailbox specifications.
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderfield('From', 'Foo <foo@example.com>, Bar <bar@example.com>')
    ]), 'Bar');
    $this->assertEquals(2, count($message->getFrom()));
    $this->assertEquals('foo@example.com', $message->getFrom()[0]->getAddress());
    $this->assertEquals('bar@example.com', $message->getFrom()[1]->getAddress());

    if (function_exists('idn_to_utf8')) {
      // Single IDN address.
      $message = new MimeMessage(new MimeHeader([new MimeHeaderField('From', 'fooBar@xn--oak-ppa56b.ba')]), 'Bar');
      $this->assertEquals('fooBar@ćošak.ba', $message->getFrom()[0]->getDecodedAddress());
    }

  }

  /**
   * Tests the recipient getter.
   *
   * @covers ::getTo
   */
  public function testGetTo() {
    // Empty recipient.
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('', '')]), 'I am a body');
    $cc_field = $message->getCC();
    $this->assertEquals([], $cc_field);

    // Single recipient address.
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('To', 'foo@example.com')]), 'Bar');
    $this->assertEquals('foo@example.com', $message->getTo()[0]->getAddress());

    // Multiple recipients.
    // @todo Parse recipients and return list.
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('Cc', 'sunshine@example.com, moon@example.com')]), 'I am a body');
    $cc_field = $message->getCC();
    $this->assertEquals(['sunshine@example.com, moon@example.com'],
      [$cc_field[0]->getAddress() . ', ' . $cc_field[1]->getAddress()]);
    // @todo Parse recipients and return list.
    // @todo Test mailbox with display name.

    if (function_exists('idn_to_utf8')) {
      // Single IDN recipient address with decoding.
      $message = new MimeMessage(new MimeHeader([new MimeHeaderField('To', 'helloWorld@xn--xample-9ua.com')]), 'Bar');
      $this->assertEquals('helloWorld@éxample.com', $message->getTo()[0]->getDecodedAddress());
    }
  }

  /**
   * Tests the Cc recipients getter.
   *
   * @covers ::getCc
   */
  public function testGetCc() {
    // Empty recipient.
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('', '')]), 'I am a body');
    $cc_field = $message->getCC();
    $this->assertEquals([], $cc_field);

    // Single recipient address.
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('Cc', 'sunshine@example.com')]), 'I am a body');
    $cc_field = $message->getCC();
    $this->assertEquals('sunshine@example.com', $cc_field[0]->getAddress());

    // Multiple recipients.
    // @todo Parse recipients and return list.
    $message = new MimeMessage(new MimeHeader([new MimeHeaderField('Cc', 'sunshine@example.com, moon@example.com')]), 'I am a body');
    $cc_field = $message->getCC();
    $this->assertEquals(['sunshine@example.com, moon@example.com'],
      [$cc_field[0]->getAddress() . ', ' . $cc_field[1]->getAddress()]);

    // @todo Also test mailbox with display name.
  }

  /**
   * Tests the Bcc recipient getter.
   *
   * @covers ::getBcc
   */
  public function testGetBcc() {
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('Bcc', 'Modern Mantra <modern_mantra@example.com>'),
    ]), 'Message body');
    $bcc_field = $message->getBcc();
    $this->assertEquals('modern_mantra@example.com', $bcc_field[0]->getAddress());
    $this->assertEquals('Modern Mantra', $bcc_field[0]->getName());
  }

  /**
   * Tests the 'Received' date getter.
   *
   * @covers ::getReceivedDate
   */
  public function testGetReceivedDate() {
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('Received', 'blah; Thu, 29 Jan 2015 15:43:04 +0100'),
    ]), 'I am a body');
    $expected_date = new DateTimePlus('Thu, 29 Jan 2015 15:43:04 +0100');
    $this->assertEquals($expected_date, $message->getReceivedDate());
    $this->assertEmpty($message->getReceivedDate()->getErrors());

    // By RFC2822 time-zone abbreviation is invalid and needs to be removed.
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('Received', 'FooBar; Fri, 21 Oct 2016 11:15:25 +0200 (CEST)'),
    ]), 'I am a body');
    $expected_date = new DateTimePlus('Fri, 21 Oct 2016 11:15:25 +0200');
    $this->assertEquals($expected_date, $message->getReceivedDate());
    $this->assertEmpty($message->getReceivedDate()->getErrors());

    $received_string = "by (localhost) via (inmail) with test_fetcher dbvMO4Ox id\n <CAFZOsfMjtXehXPGxbiLjydzCY0gCkdngokeQACWQOw+9W5drqQ@mail.example.com>; Wed, 26 Oct 2016 02:50:11 +1100 (GFT)";
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('Received', $received_string),
    ]), 'I am Body');
    $expected_date = new DateTimePlus('Wed, 26 Oct 2016 02:50:11 +1100');
    $this->assertEquals($expected_date, $message->getReceivedDate());
    // It is parsed time zone, everything else must remain untouched.
    $this->assertEquals($received_string, $message->getHeader()->getFieldBody('Received'));
    $this->assertEmpty($message->getReceivedDate()->getErrors());

    // Assert no "Received" field.
    $message = new MimeMessage(new MimeHeader(), 'Body');
    $this->assertEquals(NULL, $message->getReceivedDate());
  }

  /**
   * Tests the message is valid and contains necessary fields.
   *
   * @covers ::validate
   * @covers ::getValidationErrors
   * @covers ::setValidationError
   */
  public function testValidation() {
    // By RFC 5322 (https://tools.ietf.org/html/rfc5322#section-3.6,
    // table on p. 21), the only required MimeHeader fields are From and Date.
    // In addition, the fields can occur only once per message.
    // MimeMessage triggers checking for presence of Date and From fields,
    // as well checking single occurrence of them.
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('Delivered-To', 'alice@example.com'),
      new MimeHeaderField('Received', 'Thu, 20 Oct 2016 08:45:02 +0100'),
      new MimeHeaderField('Received', 'Fri, 21 Oct 2016 09:55:03 +0200'),
    ]), 'body');
    $this->assertFalse($message->validate());
    // Check that validation error messages exist and it is as expected.
    $this->assertArrayEquals([
      'From' => 'Missing From field.',
      'Date' => 'Missing Date field.',
    ], $message->getValidationErrors());

    // MimeMessage contains all necessary fields and only one occurrence of
    // each.
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('From', 'Foo'),
      new MimeHeaderField('Date', 'Fri, 21 Oct 2016 09:55:03 +0200'),
    ]), 'body');
    $this->assertTrue($message->validate());
    // Validation error messages should not exist.
    $this->assertEmpty($message->getValidationErrors());

    // MimeMessage contains all necessary fields but duplicates.
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('From', 'Foo'),
      new MimeHeaderField('From', 'Foo2'),
      new MimeHeaderField('Date', 'Thu, 20 Oct 2016 08:45:02 +0100'),
      new MimeHeaderField('Date', 'Fri, 21 Oct 2016 09:55:03 +0200'),
      new MimeHeaderField('Date', 'Sat, 22 Oct 2016 10:55:04 +0300'),
      new MimeHeaderField('Received', 'Thu, 20 Oct 2016 08:45:02 +0100'),
      new MimeHeaderField('Received', 'Fri, 21 Oct 2016 09:55:03 +0200'),
    ]), 'body');
    $this->assertFalse($message->validate());
    $this->assertArrayEquals([
      'From' => 'Only one occurrence of From field is allowed. Found 2.',
      'Date' => 'Only one occurrence of Date field is allowed. Found 3.',
    ], $message->getValidationErrors());
  }

  /**
   * Tests the 'Date' header getter.
   *
   * @covers::getDate
   */
  public function testGetDate() {
    $message = new MimeMessage(new MimeHeader([
      new MimeHeaderField('Date', 'Thu, 27 Oct 2016 13:29:36 +0200 (UTC)')]), 'body');
    $expected_date = new DateTimePlus('Thu, 27 Oct 2016 13:29:36 +0200');
    $this->assertEquals($expected_date, $message->getDate());
  }

}
