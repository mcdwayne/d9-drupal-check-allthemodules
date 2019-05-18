<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\MIME\MimeEntity;
use Drupal\inmail\MIME\MimeHeader;
use Drupal\inmail\MIME\MimeParser;
use Drupal\inmail\MIME\MimeDSNEntity;
use Drupal\inmail\MIME\MimeMultipartEntity;
use Drupal\Tests\inmail\Kernel\InmailTestHelperTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\inmail\MIME\MimeHeaderField;

/**
 * Tests the MimeParser, MimeEntity and MimeDSNEntity classes.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\MimeDSNEntity
 *
 * @group inmail
 * @requires module past_db
 */
class MimeDSNEntityTest extends UnitTestCase {

  use InmailTestHelperTrait;

  /**
   * Tests the parser.
   *
   * @covers \Drupal\inmail\MIME\MimeParser::parseMessage
   */
  public function testParse() {
    // Parse and compare.
    $raw = $this->getMessageFileContents('/bounce/nonexistent-address.eml');
    $parsed_message = (new MimeParser(new LoggerChannel('test')))->parseMessage($raw);
    $this->assertEquals(static::getMessage(), $parsed_message);
  }

  /**
   * @covers ::getHumanPart
   */
  public function testGetHumanPart() {
    $this->assertEquals(static::getHumanPart(), static::getMessage()->getHumanPart());
  }

  /**
   * @covers ::getStatusPart
   */
  public function testGetMachinePart() {
    $this->assertEquals(static::getStatusPart(), static::getMessage()->getStatusPart());
  }

  /**
   * @covers ::getOriginalPart
   */
  public function testGetOriginalPart() {
    $this->assertEquals(static::getOriginalPart(), static::getMessage()->getOriginalPart());
  }

  /**
   * @covers ::getPerMessageFields
   */
  public function testGetPerMessageFields() {
    $this->assertEquals(static::getPerMessageFields(), static::getMessage()->getPerMessageFields());
  }

  /**
   * @covers ::getPerRecipientFields
   */
  public function testGetPerRecipientFields() {
    $this->assertEquals(static::getPerRecipientField(), static::getMessage()->getPerRecipientFields(0));
    $this->assertEquals(NULL, static::getMessage()->getPerRecipientFields(1));
  }

  /**
   * Expected parse result of ::MSG_DSN.
   */
  protected static function getMessage() {
    // The multipart message corresponding to the final parse result.
    return new MimeDSNEntity(
      new MimeMultipartEntity(
        new MimeEntity(
          static::getMessageHeader(),
          static::getBody()
        ),
        static::getParts()
      ),
      static::getDsnFields()
    );
  }

  /**
   * Expected parse result of the header of the message (the outer entity).
   */
  protected static function getMessageHeader() {
    return new MimeHeader([
      new MimeHeaderField(
      'Content-type', 'multipart/report; report-type=delivery-status; boundary="boundary"'
      ),
    ],
      'Content-type: multipart/report; report-type=delivery-status; boundary="boundary"'
    );
  }

  /**
   * Expected parse result of the body of the message.
   */
  protected static function getBody() {
    return '--boundary

Your message could not be delivered.
--boundary
Content-Type: message/delivery-status

Reporting-MTA: dns; example.com

Final-Recipient: rfc822; user@example.org
Action: failed
Status: 5.0.0

--boundary
Content-Type: message/rfc822

Subject: My very urgent message

--boundary--
';
  }

  /**
   * Expected parse result of the parts of the message.
   */
  protected static function getParts() {
    return [
      static::getHumanPart(),
      static::getStatusPart(),
      static::getOriginalPart(),
    ];
  }

  /**
   * Expected parse result of the first part of the message.
   */
  protected static function getHumanPart() {
    return new MimeEntity(new MimeHeader(), static::getHumanPartBody());
  }

  /**
   * Expected parse result of the body of the first part of the message.
   */
  protected static function getHumanPartBody() {
    return "Your message could not be delivered.";
  }

  /**
   * Expected parse result of the second part of the message.
   */
  protected static function getStatusPart() {
    return new MimeEntity(static::getStatusPartHeader(), static::getStatusPartBody());
  }

  /**
   * Expected parse result of the header of the second part of the message.
   */
  protected static function getStatusPartHeader() {
    return new MimeHeader([
      new MimeHeaderField('Content-Type', 'message/delivery-status'),
    ],
      'Content-Type: message/delivery-status'
    );
  }

  /**
   * Expected parse result of the body of the second part of the message.
   */
  protected static function getStatusPartBody() {
    return "Reporting-MTA: dns; example.com\n\nFinal-Recipient: rfc822; user@example.org\nAction: failed\nStatus: 5.0.0\n";
  }

  /**
   * Expected parse result of the fields in the second part of the message.
   */
  protected static function getDsnFields() {
    return [
      static::getPerMessageFields(),
      static::getPerRecipientField(),
    ];
  }

  /**
   * Expected parse result of the message status fields in the second part.
   */
  protected static function getPerMessageFields() {
    return new MimeHeader([
      new MimeHeaderField('Reporting-MTA', 'dns; example.com'),
    ],
      'Reporting-MTA: dns; example.com'
    );
  }

  /**
   * Expected parse result of the receipient status fields in the second part.
   */
  protected static function getPerRecipientField() {
    return new MimeHeader([
      new MimeHeaderField('Final-Recipient', 'rfc822; user@example.org'),
      new MimeHeaderField('Action', 'failed'),
      new MimeHeaderField('Status', '5.0.0'),
    ],
      "Final-Recipient: rfc822; user@example.org\nAction: failed\nStatus: 5.0.0"
    );
  }

  /**`
   * Expected parse result of the third part of the message.
   */
  protected static function getOriginalPart() {
    return new MimeEntity(static::getOriginalPartHeader(), static::getOriginalPartBody());
  }

  /**
   * Expected parse result of the header of the third part of the message.
   */
  protected static function getOriginalPartHeader() {
    return new MimeHeader([
      new MimeHeaderField(
        'Content-Type', 'message/rfc822'
      ),
    ],
      'Content-Type: message/rfc822'
    );
  }

  /**
   * Expected parse result of the body of the third part of the message.
   */
  protected static function getOriginalPartBody() {
    return "Subject: My very urgent message\n";
  }

}
