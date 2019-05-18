<?php

namespace Drupal\Tests\jsonlog\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group JsonLog
 *
 * Class JsonLogConfigFormTest
 * @package Drupal\Tests\jsonlog\Functional
 */
class JsonLogConfigFormTest extends BrowserTestBase {

  const JSONLOG_SITEID_FIELD_NAME = 'jsonlog_siteid';

  const JSONLOG_SITEID_FIELD_VALUE = 'testname';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['jsonlog', 'node'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests that the settings page is available and we can save.
   */
  public function testLoggingConfigFormContainsJsonlogSettings() {
    $this->drupalLogin($this->user);
    $this->drupalGet(Url::fromRoute('system.logging_settings'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(t('JSON LOG'));

    $this->drupalPostForm('admin/config/development/logging',
      [self::JSONLOG_SITEID_FIELD_NAME => self::JSONLOG_SITEID_FIELD_VALUE], t('Save configuration'));

    $this->assertSession()
      ->pageTextContains(t('The configuration options have been saved.'));

    $this->drupalGet('admin/config/development/logging');
    $this->assertSession()
      ->fieldValueEquals(self::JSONLOG_SITEID_FIELD_NAME, self::JSONLOG_SITEID_FIELD_VALUE);
  }

}
