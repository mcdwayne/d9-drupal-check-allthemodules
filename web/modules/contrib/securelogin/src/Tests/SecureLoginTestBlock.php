<?php

namespace Drupal\securelogin\Tests;

/**
 * Tests Secure login with user login block enabled.
 *
 * @group Secure login
 */
class SecureLoginTestBlock extends SecureLoginTestBase {

  /**
   * Use a profile that disables the cache modules.
   *
   * @var string
   */
  protected $profile = 'testing_config_import';

  public static $modules = ['block', 'node', 'views'];

  /**
   * A user with the 'administer blocks' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer blocks', 'administer site configuration']);

    if (!$this->isSecure) {
      $this->disableModules(['securelogin']);
    }

    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('user_login_block');
    $this->drupalLogout($this->adminUser);

    if (!$this->isSecure) {
      $this->enableModules(['securelogin']);
    }
  }

  /**
   * Tests pages with user login block.
   */
  protected function testUserLoginBlock() {
    global $base_url;
    global $base_path;

    // Disable redirect following.
    $maximum_redirects = $this->maximumRedirects;
    $this->maximumRedirects = 0;

    $this->drupalGet($this->httpUrl('node'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical($headers[0]['location'], str_replace('http://', 'https://', $base_url) . '/index.php/node', 'Location header uses the secure base URL.');

    // Fetch the same URL again as it may be cached.
    $this->drupalGet($this->httpUrl('node'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical($headers[0]['location'], str_replace('http://', 'https://', $base_url) . '/index.php/node', 'Location header uses the secure base URL.');

    $this->drupalGet($this->httpUrl('admin'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical($headers[0]['location'], str_replace('http://', 'https://', $base_url) . '/index.php/admin', 'Location header uses the secure base URL.');

    $this->drupalGet($this->httpUrl('admin/config'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical($headers[0]['location'], str_replace('http://', 'https://', $base_url) . '/index.php/admin/config', 'Location header uses the secure base URL.');

    $this->drupalGet($this->httpUrl('no-page-by-this-name'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical($headers[0]['location'], str_replace('http://', 'https://', $base_url) . '/index.php/no-page-by-this-name', 'Location header uses the secure base URL.');

    $this->drupalGet($this->httpUrl('nor-this-one'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical($headers[0]['location'], str_replace('http://', 'https://', $base_url) . '/index.php/nor-this-one', 'Location header uses the secure base URL.');

    $config = $this->config('securelogin.settings');
    $this->assertTrue($config->get('secure_forms'), 'Secure forms settings is enabled by default.');

    // Disable secure forms.
    if ($this->isSecure) {
      $this->drupalLogin($this->adminUser);
      $edit['secure_forms'] = FALSE;
      $this->drupalPostForm('admin/config/people/securelogin', $edit, t('Save configuration'));
      $config = $this->config('securelogin.settings');
      $this->assertFalse($config->get('secure_forms'), 'Secure forms is disabled.');
      $this->drupalGet('user/logout');
    }
    else {
      $this->config('securelogin.settings')->set('secure_forms', FALSE)->save();
      drupal_flush_all_caches();
    }

    $this->drupalGet($this->httpUrl('node'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '200'), 'Status header contains 200.');
    $this->assertFieldByXPath('//form/@action', str_replace('http://', 'https://', $base_url) . "/index.php/node?destination={$base_path}index.php/node", 'The action attribute uses the secure base URL.');

    $this->drupalGet($this->httpUrl('admin'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '403'), 'Status header contains 403.');
    $this->assertFieldByXPath('//form/@action', str_replace('http://', 'https://', $base_url) . "/index.php/system/403?destination={$base_path}index.php/admin", 'The action attribute uses the secure base URL.');

    $this->drupalGet($this->httpUrl('admin/config'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '403'), 'Status header contains 403.');
    $this->assertFieldByXPath('//form/@action', str_replace('http://', 'https://', $base_url) . "/index.php/system/403?destination={$base_path}index.php/admin/config", 'The action attribute uses the secure base URL.');

    $this->drupalGet($this->httpUrl('no-page-by-this-name'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '404'), 'Status header contains 404.');
    $this->assertFieldByXPath('//form/@action', str_replace('http://', 'https://', $base_url) . "/index.php/system/404?destination={$base_path}index.php/", 'The action attribute uses the secure base URL.');

    $this->drupalGet($this->httpUrl('nor-this-one'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '404'), 'Status header contains 404.');
    $this->assertFieldByXPath('//form/@action', str_replace('http://', 'https://', $base_url) . "/index.php/system/404?destination={$base_path}index.php/", 'The action attribute uses the secure base URL.');

    $this->maximumRedirects = $maximum_redirects;
  }

}
