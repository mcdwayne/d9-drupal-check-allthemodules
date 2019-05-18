<?php

namespace Drupal\Tests\inmail\Unit\MIME;

use Drupal\inmail\Element\InmailMessage;
use Drupal\inmail\MIME\MimeHeader;
use Drupal\inmail\MIME\MimeMessage;
use Drupal\Tests\inmail\Unit\InmailUnitTestBase;
use Drupal\inmail\MIME\MimeHeaderField;

/**
 * Test InmailMessage class.
 *
 * @coversDefaultClass \Drupal\inmail\Element\InmailMessage
 *
 * @group inmail
 */
class InmailMessageTest extends InmailUnitTestBase {

  /**
   * Test getUnsubscriptionLink function.
   *
   * @dataProvider provideUnsubscribeHeaders
   */
  public function testGetUnsubscribeLink($header, $expected) {
    $message = new MimeMessage($header, ['']);
    $this->assertEquals($expected, InmailMessage::getUnsubsciptionLink($message));
  }

  /**
   * Provides List-Unsubscribe headers for testing.
   *
   * Those are the examples of RFC2369.
   *
   * @return array
   *   MimeHeader objects and equivalent string representations.
   */
  public function provideUnsubscribeHeaders() {
    return [
      [
        new MimeHeader([
          new MimeHeaderField(
            'List-Unsubscribe',
            '<mailto:list-manager@host.com?body=subscribe%20list>,<http://www.host.com/list.cgi?cmd=sub&lst=list>'
          ),
        ]),
        'http://www.host.com/list.cgi?cmd=sub&lst=list',
      ],
      [
        new MimeHeader([
          new MimeHeaderField(
            'List-Unsubscribe',
            '<http://www.host.com/list.cgi?cmd=sub&lst=list>, <mailto:list-manager@host.com?body=subscribe%20list>'
          ),
        ]),
        'http://www.host.com/list.cgi?cmd=sub&lst=list',
      ],
      [
        new MimeHeader([
          new MimeHeaderField(
            'List-Unsubscribe',
            '<http://www.host.com/list.cgi?cmd=sub&lst=list>,<mailto:list-manager@host.com?body=subscribe%20list>'
          ),
        ]),
        'http://www.host.com/list.cgi?cmd=sub&lst=list',
      ],
      [
        new MimeHeader([
          new MimeHeaderField(
            'List-Unsubscribe',
            '<mailto:list-off@host.com>'
          ),
        ]),
        NULL,
      ],
      [
        new MimeHeader([
          new MimeHeaderField(
            'List-Unsubscribe',
            '<mailto:list-request@host.com?subject=subscribe>'
          ),
        ]),
        NULL,
      ],
      [
        new MimeHeader([
          new MimeHeaderField(
            'List-Unsubscribe',
            ''
          ),
        ]),
        NULL,
      ]
    ];
  }

}
