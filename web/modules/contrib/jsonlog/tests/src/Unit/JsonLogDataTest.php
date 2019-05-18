<?php

namespace Drupal\Tests\jsonlog\Unit;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jsonlog\Logger\JsonLogData;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for JsonLogData class
 *
 * @group JsonLog
 *
 * Class JsonLogDataTest
 * @package Drupal\Tests\jsonlog\Unit
 */
class JsonLogDataTest extends UnitTestCase {

  const DEFAULT_THRESHOLD = 4;

  const DEFAULT_LOG_DIR = '/var/log';

  const DEFAULT_TIME_FORMAT = 'Ymd';

  const DEFAULT_SITE_ID = 'jsonlog_test';

  /**
   * @var JsonLogData $data
   */
  private $data;

  protected function setUp() {
    parent::setUp();
    $config_stub['jsonlog.settings'] = [
      'jsonlog_severity_threshold' => self::DEFAULT_THRESHOLD,
      'jsonlog_truncate' => 64,
      'jsonlog_siteid' => self::DEFAULT_SITE_ID,
      'jsonlog_canonical' => '',
      'jsonlog_file_time' => self::DEFAULT_TIME_FORMAT,
      'jsonlog_dir' => self::DEFAULT_LOG_DIR,
      'jsonlog_tags' => 'test',
    ];
  }

  /**
   * Test default initialisation of JsonLogData class
   */
  public function testCanConstructDefaultDataClass() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->assertEquals('test_site_id', $this->data->getSiteId());
    $this->assertEquals('test_canonical', $this->data->getCanonical());
    $this->assertEquals('drupal', $this->data->getType());
    $this->assertEquals(1, $this->data->getData()['@version'], '@version set to 1');
    $this->assertEmpty($this->data->getTrunc(), 'Trunc empty by default');
    $this->assertNotEmpty($this->data->getData()['@timestamp']);
    $this->assertTrue(stripos($this->data->getMessageId(), 'test_site_id') !== FALSE, 'Message ID set and contains site-id.');
    $this->assertTrue(strlen($this->data->getMessageId()) > strlen('test_site_id'), 'Message ID string is longer than site-id.');

    $first_timestamp = $this->data->getData()['@timestamp'];
    $first_message_id = $this->data->getMessageId();

    //microsleep because some fast CI environments might run tests so fast the timestamp does not differ.
    usleep(100);
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->assertFalse($first_timestamp == $this->data->getData()['@timestamp'], 'Timestamps always regenerated.');
    $this->assertFalse($first_message_id == $this->data->getMessageId(), 'Message ID unique.');
  }

  public function testCanGetDefaultDataFromInitialisedDataClass() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->assertNotEmpty($this->data->getJson(), 'Json data returned');
    $this->assertNotEmpty($this->data->getData(), 'Flat data returned');
  }

  public function testCanSetRegularMessage() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $this->assertNull($this->data->getMessage(), 'Message empty by default');

    $this->data->setMessage('test logging item');
    $this->assertEquals('test logging item', $this->data->getMessage(), 'By default message gets logged.');
    $this->assertEmpty($this->data->getTrunc(), 'Message not truncated');
  }

  public function testCanSetTranslatableMessage() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $this->assertNull($this->data->getMessage(), 'Message empty by default');

    $msg = new TranslatableMarkup('translatable logging item');
    $this->data->setMessage($msg);
    $this->assertEquals('translatable logging item', $this->data->getMessage(), 'By default message gets logged.');
    $this->assertEmpty($this->data->getTrunc(), 'Message not truncated');
  }

  public function testMessageWillBeStrippedifStartingWithHtmlMarker() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $this->assertNull($this->data->getMessage(), 'Message empty by default');

    $this->data->setMessage('<test logging item');
    $this->assertEmpty($this->data->getMessage(), 'Message gets stripped if starting with <');
    $this->assertEmpty($this->data->getTrunc(), 'Message not truncated');
  }

  public function testMessageWillNotContainNullByte() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $this->assertNull($this->data->getMessage(), 'Message empty by default');

    $this->data->setMessage('test logging ' . "\0" . ' item');
    $this->assertEquals('test logging _NUL_ item', $this->data->getMessage(), 'Message gets logged without null byte.');
    $this->assertEmpty($this->data->getTrunc(), 'Message not truncated');
  }

  public function testCanSetTruncatedMessage() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $this->assertNull($this->data->getMessage(), 'Message empty by default');

    $string = random_bytes(250);
    $this->data->setMessage($string, 1); // trunc calculated = 224 when passed 1
    $this->assertFalse(strlen($string) == strlen($this->data->getMessage()), 'Message was truncated.');
    $this->assertNotEmpty($this->data->getTrunc(), 'Data truncation value set');
  }

  public function testLogLevelIsConvertedToRFCLogLevel() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setSeverity(3);
    $this->assertEquals(new TranslatableMarkup('Error'), $this->data->getSeverity(), 'LogLevel 3 set to Error.');
  }

  public function testChannelWillBeSetAsSubtypeWithMaxLength() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setSubType(404);
    $this->assertEquals('404', $this->data->getSubtype(), 'Subtype set.');

    // Try bigger
    $string = random_bytes(1024);
    $this->data->setSubType($string);
    $this->assertFalse(strlen($string) == strlen($this->data->getSubtype()), 'Subtype was truncated.');
  }

  public function testClientIpWillBeSetWithMaxLength() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setClient_ip('127.0.0.1');
    $this->assertEquals('127.0.0.1', $this->data->getClientIp(), 'Client IP set.');

    // Try bigger
    $string = random_bytes(1024);
    $this->data->setClient_ip($string);
    $this->assertFalse(strlen($string) == strlen($this->data->getClientIp()), 'Client IP was truncated.');
  }

  public function testCanSetUsernameAndUidBasedOnAccount() {
    $accountMock = $this->createMock('Drupal\Core\Session\AccountProxyInterface');
    $accountMock->expects($this->exactly(1))
      ->method('getAccountName')
      ->willReturn('dummy');
    $accountMock->expects($this->exactly(1))
      ->method('id')
      ->willReturn(1234);

    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    /** @var \Drupal\Core\Session\AccountProxyInterface $accountMock */
    $this->data->setAccount($accountMock);
    $this->assertEquals('dummy', $this->data->getUsername(), 'Username set.');
    $this->assertEquals(1234, $this->data->getUid(), 'UID set.');
  }

  public function testLinkAndCodeWillAlwaysBeSetAccordinglyWithAlphanumericString() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setLink('abcdef');
    $this->assertEquals('abcdef', $this->data->getLink(), 'Link set.');
    $this->assertEquals(0, $this->data->getCode(), 'Code set.');
  }

  public function testLinkAndCodeWillAlwaysBeSetAccordinglyWithNumericString() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setLink('1234');
    $this->assertNull($this->data->getLink(), 'Link not set.');
    $this->assertEquals(1234, $this->data->getCode(), 'Code set.');
  }

  public function testLinkAndCodeWillAlwaysBeSetAccordinglyWithInteger() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setLink(1234);
    $this->assertNull($this->data->getLink(), 'Link not set.');
    $this->assertEquals(1234, $this->data->getCode(), 'Code set.');
  }

  public function testLinkAndCodeWillAlwaysBeSetAccordinglyWithZero() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setLink(0);
    $this->assertNull($this->data->getLink(), 'Link not set.');
    $this->assertEquals(0, $this->data->getCode(), 'Code not set.');
  }

  public function testLinkAndCodeWillAlwaysBeSetAccordinglyWithBoolFalse() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setLink(FALSE);
    $this->assertNull($this->data->getLink(), 'Link not set.');
    $this->assertEquals(0, $this->data->getCode(), 'Code not set.');
  }

  public function testServerAndSiteTagsAreCombined() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $server_tags = 'a,b';
    $site_tags = 'x,y';

    $this->data->setTags($server_tags, $site_tags);
    $this->assertArrayEquals([
      'a',
      'b',
      'x',
      'y',
    ], $this->data->getTags(), 'All tags present.');
  }

  public function testServerTagsCanBeSetAlone() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $server_tags = 'a,b';

    $this->data->setTags($server_tags, '');
    $this->assertArrayEquals([
      'a',
      'b',
    ], $this->data->getTags(), 'All server tags present.');
  }

  public function testSiteTagsCanBeSetAlone() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');
    $site_tags = 'x,y';

    $this->data->setTags('', $site_tags);
    $this->assertArrayEquals([
      'x',
      'y',
    ], $this->data->getTags(), 'All site tags present.');
  }

  public function testSiteTagsCanBeEmpty() {
    $this->data = new JsonLogData('test_site_id', 'test_canonical');

    $this->data->setTags('', '');
    $this->assertNull($this->data->getTags(), 'No tags available.');
  }

}