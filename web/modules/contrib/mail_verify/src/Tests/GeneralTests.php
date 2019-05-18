<?php

namespace Drupal\mail_verify\Tests;

use Drupal\Core\Cache\CacheBackendInterface;

use Drupal\Component\Utility\Unicode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;

use Drupal\Core\Site\Settings;

/**
 * Testea funciones basicas.
 *
 * @group Cache
 */
abstract class GeneralTests {

  /**
   * The email validator.
   *
   * @var \Drupal\mail_verify\MailVerify
   */
  protected $validator;

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test clearing using a cid.
   */
  public static function testValidate() {

    $logger =  new \Drupal\Core\Logger\LoggerChannelFactory();
    $keyvalue = new \Drupal\Core\KeyValueStore\KeyValueNullExpirableFactory();
    $cache = new \Drupal\Core\Cache\MemoryBackendFactory();

    $validator = new \Drupal\mail_verify\MailVerify($cache, $logger, $keyvalue);

    $validator->isValid('wapteams@microsoft.com', TRUE, TRUE);
  }

}