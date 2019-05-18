<?php

namespace Drupal\Tests\noreferrer\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the No Referrer module.
 *
 * @group No Referrer
 */
class NoReferrerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['noreferrer'];

  /**
   * Functional tests for the rel="noreferrer" attribute.
   */
  public function testNoReferrer() {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $edit = ['whitelisted_domains' => 'drupal.org example.org'];
    $this->drupalPostForm('admin/config/content/noreferrer', $edit, t('Save configuration'));
    $this->assertIdentical((string) \Drupal::l('test', Url::fromUri('https://example.com/')), '<a href="https://example.com/" rel="noreferrer">test</a>');
    $this->assertIdentical((string) \Drupal::l('test', Url::fromUri('https://drupal.org/')), '<a href="https://drupal.org/">test</a>');
    $this->assertIdentical((string) \Drupal::l('test', Url::fromUri('https://drupal.org/', ['attributes' => ['target' => '_blank']])), '<a href="https://drupal.org/" target="_blank" rel="noopener">test</a>');
    $this->assertIdentical((string) \Drupal::l('test', Url::fromUri('https://DRUPAL.ORG/')), '<a href="https://DRUPAL.ORG/">test</a>');
    $this->assertIdentical((string) \Drupal::l('test', Url::fromUri('https://api.drupal.org/')), '<a href="https://api.drupal.org/">test</a>');
    $this->assertIdentical((string) \Drupal::l('test', Url::fromUri('https://example.com/', ['attributes' => ['target' => '_new_tab']])), '<a href="https://example.com/" target="_new_tab" rel="noopener noreferrer">test</a>');
    $this->assertIdentical((string) \Drupal::l('test', Url::fromUri('https://example.org/', ['attributes' => ['target' => '0']])), '<a href="https://example.org/" target="0" rel="noopener">test</a>');
  }

}
