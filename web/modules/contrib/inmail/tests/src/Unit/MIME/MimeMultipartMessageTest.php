<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\inmail\MIME\MimeEntity;
use Drupal\inmail\MIME\MimeHeader;
use Drupal\inmail\MIME\MimeHeaderField;
use Drupal\inmail\MIME\MimeMultipartMessage;

/**
 * Tests multipart messages.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\MimeMultipartMessage
 *
 * @group inmail
 */
class MimeMultipartMessageTest extends MimeMultipartEntityTest {

  /**
   * Tests the multipart message is valid and contains necessary fields.
   *
   * @covers ::validate
   * @covers ::getValidationErrors
   * @covers ::setValidationError
   */
  public function testValidation() {
    $multipart_message = new MimeMultipartMessage(new MimeEntity(static::getMessageHeader(), static::getBody()), []);
    // Message contains all necessary fields and only one occurrence of each.
    $this->assertTrue($multipart_message->validate());
    // Validation error messages should not exist.
    $this->assertEmpty($multipart_message->getValidationErrors());

    // By RFC 5322 (https://tools.ietf.org/html/rfc5322#section-3.6,
    // table on p. 21), the only required header fields are From and Date.
    // In addition, the fields can occur only once per message.
    // Message triggers checking for presence of Date and From fields,
    // as well checking single occurrence of them.
    $missing_fields_header = new MimeHeader([
      new MimeHeaderField('Delivered-To', 'alice@example.com'),
      new MimeHeaderField('Received', 'body', 'Fri, 21 Oct 2016 09:55:03 +0200'),
    ]);
    $multipart_message = new MimeMultipartMessage(new MimeEntity($missing_fields_header, static::getBody()), []);
    $this->assertFalse($multipart_message->validate());
    // Check that validation error messages are present and as expected.
    $this->assertArrayEquals([
      'From' => 'Missing From field.',
      'Date' => 'Missing Date field.',
    ], $multipart_message->getValidationErrors());

    // Message contains all necessary fields but duplicates.
    $duplicate_fields_header = new MimeHeader([
      new MimeHeaderField('From', 'Foo'),
      new MimeHeaderField('From', 'Foo2'),
      new MimeHeaderField('Date', 'Thu, 20 Oct 2016 08:45:02 +0100'),
      new MimeHeaderField('Date', 'Fri, 21 Oct 2016 09:55:03 +0200'),
      new MimeHeaderField('Date', 'Sat, 22 Oct 2016 10:55:04 +0300'),
      new MimeHeaderField('Received', 'Thu, 20 Oct 2016 08:45:02 +0100'),
      new MimeHeaderField('Received', 'Fri, 21 Oct 2016 09:55:03 +0200'),
    ]);
    $multipart_message = new MimeMultipartMessage(new MimeEntity($duplicate_fields_header, static::getBody()), []);
    $this->assertFalse($multipart_message->validate());
    $this->assertArrayEquals([
      'From' => 'Only one occurrence of From field is allowed. Found 2.',
      'Date' => 'Only one occurrence of Date field is allowed. Found 3.',
    ], $multipart_message->getValidationErrors());
  }

}
