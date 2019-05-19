<?php

namespace Drupal\Tests\yasm_charts\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a class for Yasm charts functional tests.
 *
 * @group yasm
 */
class YasmChartsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'file', 'user', 'yasm_charts'];

  /**
   * Admin users with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $yasmUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create yasm user.
    $this->yasmUser = $this->drupalCreateUser([
      'view the administration theme',
      'access administration pages',
      'yasm contents',
      'yasm users',
      'yasm files',
      'yasm my contents',
    ]);
  }

  /**
   * Tests users access.
   */
  public function testsYasmCharts() {
    $this->drupalLogin($this->yasmUser);

    $message = t('To display charts you first need to set up a charts library in <a href="@link">charts settings</a>.', [
      '@link' => '/admin/config/content/charts',
    ]);

    // Tests routes without defined charts library and checks alert message.
    $this->drupalGet('admin/reports/yasm/site/contents');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($message);

    $this->drupalGet('admin/reports/yasm/site/users');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($message);

    $this->drupalGet('admin/reports/yasm/site/files');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($message);

    $this->drupalGet('admin/reports/yasm/my/contents');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains($message);

    // Set a library and tests routes again.
    $config = \Drupal::service('config.factory')->getEditable('charts.settings');
    $default_settings = $config->get('charts_default_settings');
    $default_settings['library'] = 'google';
    $config->set('charts_default_settings', $default_settings);
    $config->save();

    $this->drupalGet('admin/reports/yasm/site/contents');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($message);

    $this->drupalGet('admin/reports/yasm/site/files');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($message);

    $this->drupalGet('admin/reports/yasm/my/contents');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseNotContains($message);
  }

}
