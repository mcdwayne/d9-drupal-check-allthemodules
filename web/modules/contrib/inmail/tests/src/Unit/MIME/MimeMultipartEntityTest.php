<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\MIME\MimeEntity;
use Drupal\inmail\MIME\MimeHeader;
use Drupal\inmail\MIME\MimeHeaderField;
use Drupal\inmail\MIME\MimeMultipartMessage;
use Drupal\inmail\MIME\MimeParser;
use Drupal\Tests\inmail\Kernel\InmailTestHelperTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MimeParser, Entity and MultipartEntity classes.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\MimeMultipartEntity
 *
 * @group inmail
 * @requires module past_db
 */
class MimeMultipartEntityTest extends UnitTestCase {

  use InmailTestHelperTrait;

  /**
   * Tests the parser.
   *
   * @covers \Drupal\inmail\MIME\MimeParser::parseMessage
   */
  public function testParse() {
    // Parse and compare.
    $raw = $this->getMessageFileContents('/multipart-attachment/simple-multipart-created-by-rfc.eml');
    $parsed_message = (new MimeParser(new LoggerChannel('test')))->parseMessage($raw);
    $this->assertEquals(static::getMessage(), $parsed_message);
  }

  /**
   * Tests header accessors.
   *
   * @covers \Drupal\inmail\MIME\MimeEntity::getHeader
   */
  public function testGetHeader() {
    // Compare the whole header.
    $this->assertEquals(static::getMessageHeader(), static::getMessage()->getHeader());
    $this->assertEquals(new MimeHeader(), static::getFirstPart()->getHeader());
    $this->assertEquals(static::getSecondPartHeader(), static::getSecondPart()->getHeader());
  }

  /**
   * Tests the multipart part accessor.
   *
   * @covers ::getPart
   */
  public function testGetPart() {
    $this->assertEquals(static::getFirstPart(), static::getMessage()->getPart(0));
    $this->assertEquals(static::getSecondPart(), static::getMessage()->getPart(1));
    $this->assertNull(static::getMessage()->getPart(2));
  }

  /**
   * Tests the body accessor.
   *
   * @covers \Drupal\inmail\MIME\MimeEntity::getBody
   */
  public function testGetBody() {
    $this->assertEquals("This is implicitly typed plain US-ASCII text.\nIt does NOT end with a linebreak.", static::getFirstPart()->getBody());
    $this->assertEquals("This is explicitly typed plain US-ASCII text.\nIt DOES end with a linebreak.\n", static::getSecondPart()->getBody());
    $this->assertEquals(static::getBody(), static::getMessage()->getBody());
  }

  /**
   * Tests the body accessors in the context of decoding.
   *
   * @covers \Drupal\inmail\MIME\MimeEntity::getBody
   * @covers \Drupal\inmail\MIME\MimeEntity::getDecodedBody
   *
   * @dataProvider provideEncodedEntities
   */
  public function testGetBodyUndecoded(MimeHeader $header, $body, $decoded_body) {
    $entity = new MimeEntity($header, $body);
    $this->assertEquals($body, $entity->getBody());
    $this->assertEquals($decoded_body, $entity->getDecodedBody());
  }

  /**
   * Tests string serialization.
   *
   * @covers \Drupal\inmail\MIME\MimeEntity::toString
   */
  public function testToString() {
    $raw = $this->getMessageFileContents('/multipart-attachment/simple-multipart-created-by-rfc.eml');
    $this->assertEquals($raw, static::getMessage()->toString());
  }

  /**
   * Just to make it obvious, test that toString() inverts parseMessage().
   */
  public function testParseToString() {
    $parser = new MimeParser(new LoggerChannel('test'));

    // Parse and back again.
    $raw = $this->getMessageFileContents('/multipart-attachment/simple-multipart-created-by-rfc.eml');
    $parsed = $parser->parseMessage($raw);
    $this->assertEquals($raw, $parsed->toString());

    // To string and back again.
    $string = static::getMessage()->toString();
    $this->assertEquals(static::getMessage(), $parser->parseMessage($string));
  }

  /**
   * Expected parse result of ::MSG_MULTIPART.
   */
  protected static function getMessage() {
    // The multipart message corresponding to the final parse result.
    return new MimeMultipartMessage(
      new MimeEntity(static::getMessageHeader(), static::getBody()),
      [
        static::getFirstPart(),
        static::getSecondPart(),
      ]
    );
  }

  /**
   * Expected parse result of the header of the message (the outer entity).
   */
  protected static function getMessageHeader() {
    return new MimeHeader([
      new MimeHeaderField('From', 'Nathaniel Borenstein <nsb@bellcore.com>'),
      new MimeHeaderField('To', 'Ned Freed <ned@innosoft.com>'),
      new MimeHeaderField('Date', 'Sun, 21 Mar 1993 23:56:48 -0800 (PST)'),
      new MimeHeaderField('Subject', 'Sample message'),
      new MimeHeaderField('MIME-Version', '1.0'),
      new MimeHeaderField('Content-type', 'multipart/mixed; boundary="simple boundary"'),
    ],
      "From: Nathaniel Borenstein <nsb@bellcore.com>\nTo: Ned Freed <ned@innosoft.com>\n" .
      "Date: Sun, 21 Mar 1993 23:56:48 -0800 (PST)\nSubject: Sample message\n" .
      "MIME-Version: 1.0\nContent-type: multipart/mixed; boundary=\"simple boundary\""
  );
  }

  /**
   * Expected parse result of the body of the message.
   */
  protected static function getBody() {
    return 'This is the preamble.  It is to be ignored, though it
is a handy place for composition agents to include an
explanatory note to non-MIME conformant readers.

--simple boundary

This is implicitly typed plain US-ASCII text.
It does NOT end with a linebreak.
--simple boundary
Content-type: text/plain; charset=us-ascii

This is explicitly typed plain US-ASCII text.
It DOES end with a linebreak.

--simple boundary--

This is the epilogue.  It is also to be ignored.
';
  }

  /**
   * Expected parse result of the first multipart part.
   */
  protected static function getFirstPart() {
    return new MimeEntity(new MimeHeader(), "This is implicitly typed plain US-ASCII text.\nIt does NOT end with a linebreak.");
  }

  /**
   * Expected parse result of the second multipart part.
   */
  protected static function getSecondPart() {
    return new MimeEntity(static::getSecondPartHeader(), "This is explicitly typed plain US-ASCII text.\nIt DOES end with a linebreak.\n");
  }

  /**
   * Expected parse result of the header of the second multipart part.
   */
  protected static function getSecondPartHeader() {
    return new MimeHeader([
      new MimeHeaderField('Content-type', 'text/plain; charset=us-ascii'),
    ],
      'Content-type: text/plain; charset=us-ascii'
    );
  }

  /**
   * Provides entities with encoded bodies.
   *
   * @return array
   *   A list of triplets containing a header with encoding/charset fields, a
   *   body encoded accordingly, and the body unencoded.
   */
  public static function provideEncodedEntities() {
    return [
      [
        new MimeHeader([
          new MimeHeaderField('Content-Type', 'text/plain; charset=UTF-8'),
          new MimeHeaderField('Content-Transfer-Encoding', 'quoted-printable'),
        ]),
        '=E6=97=A5=E6=9C=AC=E5=9B=BD',
        '日本国',
      ],
    ];
  }

}
