<?php

namespace Drupal\Tests\amazon_sns\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the global settings form.
 *
 * @group amazon_sns
 */
class SnsSettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'amazon_sns',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test the logging configuration form.
   */
  public function testLoggingConfig() {
    // Test that logging is off by default.
    $config = $this->config('amazon_sns.settings');
    $this->assertFalse($config->get('log_notifications'));

    // Test enabling logging.
    $this->drupalGet('admin/config/services/amazon-sns');
    $page = $this->getSession()->getPage();
    $page->checkField('Enable logging of all inbound SNS notifications');
    $page->pressButton('Save configuration');
    $page = $this->getSession()->getPage();
    $field = $page->findField('Enable logging of all inbound SNS notifications');
    $this->assertTrue($field->isChecked());

    $this->container->get('config.factory')->reset('amazon_sns.settings');
    $config = $this->config('amazon_sns.settings');
    $this->assertTrue($config->get('log_notifications'));

    // Test disabling logging.
    $this->drupalGet('admin/config/services/amazon-sns');
    $page = $this->getSession()->getPage();
    $page->uncheckField('Enable logging of all inbound SNS notifications');
    $page->pressButton('Save configuration');
    $page = $this->getSession()->getPage();
    $field = $page->findField('Enable logging of all inbound SNS notifications');
    $this->assertFalse($field->isChecked());

    $this->container->get('config.factory')->reset('amazon_sns.settings');
    $config = $this->config('amazon_sns.settings');
    $this->assertFalse($config->get('log_notifications'));
  }

}
