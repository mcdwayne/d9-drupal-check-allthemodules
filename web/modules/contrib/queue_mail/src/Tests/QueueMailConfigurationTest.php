<?php

namespace Drupal\queue_mail\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests configuration of Queue mail module.
 *
 * @group queue_mail
 */
class QueueMailConfigurationTest extends WebTestBase {
  use StringTranslationTrait;

  /**
   * Admin user.
   */
  protected $admin_user;

  const CONFIGURATION_PATH = 'admin/config/system/queue_mail';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('queue_mail');

  public static function getInfo() {
    return array(
      'name' => 'Configuration of Queue Mail',
      'description' => "Ensures that an administrator can configure the queue mail module on the site.",
      'group' => 'Mail',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer site configuration'));
  }

  /**
   * Ensures queue_mail_keys setting can be changed.
   */
  function testKeysConfiguration() {
    $this->drupalLogin($this->admin_user);

    // Test default value.
    $this->drupalGet(self::CONFIGURATION_PATH);
    $this->assertFieldByName('queue_mail_keys', '');

    // Test change of setting.
    $this->drupalPostForm(self::CONFIGURATION_PATH, array('queue_mail_keys' => 'queue_mail_test'), $this->t('Save configuration'));
    $this->assertFieldByName('queue_mail_keys', 'queue_mail_test');
  }

  /**
   * Ensures queue_mail_queue_time setting can be changed.
   */
  function testTimesConfiguration() {
    $this->drupalLogin($this->admin_user);

    // Test default value.
    $this->drupalGet(self::CONFIGURATION_PATH);
    $this->assertFieldByName('queue_mail_queue_time', '15');

    // Test change of setting.
    $this->drupalPostForm(self::CONFIGURATION_PATH, array('queue_mail_queue_time' => '30'), $this->t('Save configuration'));
    $this->assertFieldByName('queue_mail_queue_time', '30');
  }
}
