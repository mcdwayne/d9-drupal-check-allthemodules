<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\MIME\MimeParser;
use Drupal\inmail\MIME\Rfc2822Address;
use Drupal\Tests\inmail\Unit\InmailUnitTestBase;

/**
 * Tests the MimeParser class.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\MimeParser
 *
 * @group inmail
 */
class MimeParserTest extends InmailUnitTestBase {

  /**
   * Tests that an exception is thrown when parsing fails.
   *
   * @covers ::parseMessage
   *
   * @dataProvider provideMalformedRaws
   *
   * @expectedException \Drupal\inmail\MIME\MimeParseException
   */
  public function testParseException($raw) {
    $message = (new MimeParser(new LoggerChannel('test')))->parseMessage($raw);
    // The RFC standard 4475 (https://tools.ietf.org/html/rfc4475#section-3.1.2),
    // defines some critical samples of invalid messages.
    \Drupal::logger('test')->log('ParserTest', "Message is missing blank line after header");
    \Drupal::logger('test')->log('ParserTest', "Content Length Larger than Message");
    \Drupal::logger('test')->log('ParserTest', "Negative Content Length");
    \Drupal::logger('test')->log('ParserTest', "Undetermined Quoted String");
    \Drupal::logger('test')->log('ParserTest', "Message does not contain Required Fields From, To");
    \Drupal::logger('test')->log('ParserTest', "Invalid time zone in Date field");
    \Drupal::logger('test')->log('ParserTest', "Message contains multiple Fields From, To");
  }

  /**
   * Provides invalid entities that should cause the parser to fail.
   */
  public function provideMalformedRaws() {
    return [
      [$this->getRaw('malformed/unseparated_body.eml')],
      // Message has Content-Length that is larger than actual length of body.
      ["To: sip:j.user@example.com
      From: sip:caller@example.net;tag=93942939o2
      Content-Length: 9999"],
      // MimeMessage has Negative value for Content-Length.
      ["To: sip:j.user@example.com
      From: sip:caller@example.net;tag=32394234
      Content-Length: -999"],
      // To MimeHeader contains undetermined quote string.
      ["To: \"Mr. J. User sip:j.user@example.com
      From: sip:caller@example.net;tag=93334"],
      // Missing Required MimeHeader Fields From, To.
      ["This is body of message without any headers"],
      // Date MimeHeader contains a non-GMT time zone.
      ["To: sip:user@example.com
      From: sip:caller@example.net;tag=2234923
      Date: Fri, 01 Jan 2010 16:00:00 EST"],
      // Multiple To, From fields that should occur once.
      ["From: sip:caller@example.com;tag=3413415
      To: sip:user@example.com
      To: sip:other@example.net
      From: sip:caller@example.net;tag=2923420123"],
    ];
  }

  /**
   * Test address parsing.
   *
   * @covers ::parseAddress
   *
   * @dataProvider provideAddresses
   */
  public function testParseAddress($field, $expected) {
    $this->assertEquals($expected, MimeParser::parseAddress($field));
  }

  /**
   * Provide email address fields to test parseAddress with.
   */
  public static function provideAddresses() {
    return [
      // Spaces.
      [' admin@example.com ', [
        new Rfc2822Address('', 'admin@example.com'),
      ]],
      // Multiple.
      ['a@b.c, d.e@f.g.h', [
        new Rfc2822Address('', 'a@b.c'),
        new Rfc2822Address('', 'd.e@f.g.h'),
      ]],
      // With name.
      ['Admin <admin@example.com>', [
        new Rfc2822Address('Admin', 'admin@example.com'),
      ]],
      // With quote-enclosed name.
      ['"Admin" <admin@example.com>', [
        new Rfc2822Address('Admin', 'admin@example.com'),
      ]],
      // Multiple with name.
      ['Admin <admin@example.com>, User <user.name@users.example.com>', [
        new Rfc2822Address('Admin', 'admin@example.com'),
        new Rfc2822Address('User', 'user.name@users.example.com'),
      ]],
      // Comma in name (resolves to multiple, where first is invalid).
      ['Admin, Bedmin <admin@example.com>', [
        new Rfc2822Address('Bedmin', 'admin@example.com'),
      ]],
      // Address in quotes but not after (invalid).
      ['"Admin, Admin <admin@example.com>"', []],
      // @todo Allow comma in name, https://www.drupal.org/node/2475057
//      // Comma in name (quoted, valid).
//      ['"Admin, Admin" <admin@example.com>', [
//        new Rfc2822Address('Admin, Admin', 'admin@example.com'),
//      ]],
//      // Address in quotes and after.
//      ['"Admin, Admin <admin@example.com>" <admin@example.com>', [
//        new Rfc2822Address('Admin <admin@example.com>', 'admin@example.com'),
//      ]],
      // Unicode in name.
      ['Admin™ <admin@example.com>', [
        new Rfc2822Address('Admin™', 'admin@example.com'),
      ]],
      // Sub-address extension pattern.
      ['Admin <admin+admin@example.com>', [
        new Rfc2822Address('Admin', 'admin+admin@example.com'),
      ]],
    ];
  }

}
