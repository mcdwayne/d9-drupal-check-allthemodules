<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\inmail\MIME\MimeHeader;
use Drupal\inmail\MIME\MimeHeaderField;
use Drupal\inmail\MIME\MimeParser;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MimeHeader class.
 *
 * @coversDefaultClass \Drupal\inmail\MIME\MimeHeader
 *
 * @group inmail
 */
class MimeHeaderTest extends UnitTestCase {

  /**
   * Tests string serialization.
   *
   * @covers ::toString
   *
   * @dataProvider provideHeaders
   */
  public function testToString(MimeHeader $header, $string) {
    $this->assertEquals($string, $header->toString());
  }

  /**
   * Tests header parsing.
   *
   * @covers \Drupal\inmail\MIME\MimeParser::parseHeaderFields
   *
   * @dataProvider provideHeaders
   */
  public function testParse(MimeHeader $header, $string) {
    $this->assertEquals($header, (new MimeParser(new LoggerChannel('')))->parseHeaderFields($string));
  }

  /**
   * Tests if there is a field.
   *
   * @covers ::hasField
   *
   * @dataProvider provideHeadersHasField
   */
  public function testHasField(MimeHeader $header, $expected) {
    $this->assertEquals($expected, $header->hasField('Content-Type'));
  }

  /**
   * Provides header objects for testing testHasField().
   *
   * @return array
   *   MimeHeader objects and expected representations.
   */
  public function provideHeadersHasField() {
    return [
      [
        new MimeHeader([
          new MimeHeaderField(
            'Content-Type',
            'Multipart/Report; report-type=delivery-status; boundary="========/528515BF03161E46/smtp-in13.han.skanova.net"'
          )]),
        TRUE,
      ],
      [
        new MimeHeader([
          new MimeHeaderField(
            'content-type',
            'Multipart/Report; report-type=delivery-status; boundary="========/528515BF03161E46/smtp-in13.han.skanova.net"'
          )]),
        TRUE,
      ],
      [
        new MimeHeader(),
        FALSE,
      ],
    ];
  }

  /**
   * Provides header objects for testing toString().
   *
   * @return array
   *   MimeHeader objects and equivalent string representations.
   */
  public function provideHeaders() {
    return [
      [
        new MimeHeader([
          new MimeHeaderField(
            'Content-Type',
            'Multipart/Report; report-type=delivery-status; boundary="========/528515BF03161E46/smtp-in13.han.skanova.net"'
          )
        ],
          "Content-Type: Multipart/Report; report-type=delivery-status;\n"
          . " boundary=\"========/528515BF03161E46/smtp-in13.han.skanova.net\""
        ),
        // The 78 char limit is somewhere in the middle of the boundary. The
        // line folding algorithm must break before the last space before that
        // limit.
        "Content-Type: Multipart/Report; report-type=delivery-status;\n"
        . " boundary=\"========/528515BF03161E46/smtp-in13.han.skanova.net\"",
      ],
      [
        new MimeHeader([
          new MimeHeaderField(
            'Subject',
            // The ü in this string triggers base64 encoding in toString. Encoded
            // string wraps within the 78 char line limit.
            "Alle Menschen sind frei und gleich an Würde und Rechten geboren. Sie sind mit Vernunft und Gewissen begabt und sollen einander im Geist der Brüderlichkeit begegnen."
          )],
          "Subject: =?UTF-8?B?QWxsZSBNZW5zY2hlbiBzaW5kIGZyZWkgdW5kIGdsZWljaCBhbiBX?=\n"
          . " =?UTF-8?B?w7xyZGUgdW5kIFJlY2h0ZW4gZ2Vib3Jlbi4gU2llIHNpbmQgbWl0IFZlcm51bmY=?=\n"
          . " =?UTF-8?B?dCB1bmQgR2V3aXNzZW4gYmVnYWJ0IHVuZCBzb2xsZW4gZWluYW5kZXIgaW0gR2U=?=\n"
          . " =?UTF-8?B?aXN0IGRlciBCcsO8ZGVybGljaGtlaXQgYmVnZWduZW4u?="
        ),
        "Subject: =?UTF-8?B?QWxsZSBNZW5zY2hlbiBzaW5kIGZyZWkgdW5kIGdsZWljaCBhbiBX?=\n"
        . " =?UTF-8?B?w7xyZGUgdW5kIFJlY2h0ZW4gZ2Vib3Jlbi4gU2llIHNpbmQgbWl0IFZlcm51bmY=?=\n"
        . " =?UTF-8?B?dCB1bmQgR2V3aXNzZW4gYmVnYWJ0IHVuZCBzb2xsZW4gZWluYW5kZXIgaW0gR2U=?=\n"
        . " =?UTF-8?B?aXN0IGRlciBCcsO8ZGVybGljaGtlaXQgYmVnZWduZW4u?=",
      ],
    ];
  }

  /**
   * Tests adding field to the header.
   *
   * @covers ::addField
   *
   * @dataProvider provideHeaders
   */
  public function testAddField(MimeHeader $header) {
    $header->addField(new MimeHeaderField('X-Space-MimeHeader', 'Alienware'));
    $this->assertTrue($header->hasField('X-Space-MimeHeader'));
  }

  /**
   * Tests removing field from the header.
   *
   * @covers ::removeField
   *
   * @dataProvider provideHeadersHasField
   */
  public function testRemoveField(MimeHeader $header) {
    $header->removeField('Content-Type');
    $this->assertFalse($header->hasField('Content-Type'));
  }

  /**
   * Tests getting bodies with given name.
   *
   * @covers ::getFieldBodies
   */
  public function testGetFieldBodies() {
    $header = new MimeHeader([
      new MimeHeaderField(
        'Subject',
        'I am Your Subject Body'
      )]);
    $this->assertEquals($header->getFieldBodies('Subject')[0], "I am Your Subject Body");
    $this->assertEquals(count($header->getFieldBodies('Subject')), 1);
  }

  /**
   * Tests getting body with the given name.
   *
   * @covers ::getFieldBody
   *
   * @dataProvider provideHeaders
   */
  public function testGetFieldBody() {
    $header = new MimeHeader([
      new MimeHeaderField(
        'Content-Type',
        'Gruezi ! Alle Menschen sind frei und gleich an Würde und Rechten geboren'
      )]);
    $this->assertEquals('Gruezi ! Alle Menschen sind frei und gleich an Würde und Rechten geboren', $header->getFieldBody('Content-Type'));
  }

  /**
   * Tests the getRaw function.
   */
  public function testGetRaw() {
    $header = new MimeHeader([
      new MimeHeaderField(
        'Content-Type',
        'Gruezi ! Alle Menschen sind frei und gleich an Würde und Rechten geboren'
      )],
      'Content-Type: Gruezi ! Alle Menschen sind frei und gleich an Würde und Rechten geboren'
    );
    $this->assertEquals($header->getRaw(), 'Content-Type: Gruezi ! Alle Menschen sind frei und gleich an Würde und Rechten geboren');
  }

  /**
   * Tests getting header fields.
   *
   * @covers ::getFields
   */
  public function testGetFields() {
    $headers = new MimeHeader([
      new MimeHeaderField('From', 'Foo'),
      new MimeHeaderField('To', 'Bar'),
      new MimeHeaderField('Content-Type', 'text/html'),
    ], 'From: Foo\nTo: Bar\nContent-Type:text/html');
    $this->assertEquals(3, count($headers->getFields()));
    $this->assertEquals(new MimeHeaderField('From', 'Foo'), $headers->getFields()[0]);
    $this->assertEquals(new MimeHeaderField('To', 'Bar'), $headers->getFields()[1]);
    $this->assertEquals(new MimeHeaderField('Content-Type', 'text/html'), $headers->getFields()[2]);
  }

}
