<?php

namespace Drupal\mobile_js_redirect\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the Mobile JS Redirect module.
 *
 * @group Mobile Js Redirect
 */
class MobileJsRedirectTests extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('mobile_js_redirect');

  /**
   * {@inheritdoc}
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();
    $this->user = $this->DrupalCreateUser(array(
      'administer site configuration',
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function testMobileJsRedirectPageExists() {

    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('admin/config/system/mobile_js_redirect');
    $this->assertResponse(200);
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigForm() {

    // Test form structure.
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/system/mobile_js_redirect');
    $this->assertResponse(200);
    $config = $this->config('mobile_js_redirect.settings');
    $this->assertFieldByName(
      'mobile_js_redirect_urls',
      $config->get('mobile_js_redirect_urls'),
      'Page title field has the default value'
    );
    $this->assertFieldByName(
      'mobile_js_redirect_regexp_devices_list',
      $config->get('mobile_js_redirect_regexp_devices_list'),
      'Page title field has the default value'
    );

    $this->drupalPostForm(NULL, array(
      'mobile_js_redirect_urls' => 'targetexample|desktopredirect|mobileredirect',
      'mobile_js_redirect_regexp_devices_list' => 'iphone|ipad|ipod|android|blackberry|mini|windowssce|iemobile|palm',
    ), t('Save configuration'));

    $this->drupalGet('admin/config/system/mobile_js_redirect');
    $this->assertResponse(200);
    $this->assertFieldByName(
      'mobile_js_redirect_urls',
      'targetexample|desktopredirect|mobileredirect',
      'URLs text is OK.'
    );
    $this->assertFieldByName(
      'mobile_js_redirect_regexp_devices_list',
      'iphone|ipad|ipod|android|blackberry|mini|windowssce|iemobile|palm',
      'Devices List text is OK.'
    );
  }

}
