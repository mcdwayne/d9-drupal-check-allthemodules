<?php

namespace Drupal\securelogin\Tests;

/**
 * Tests secure login module configuration.
 *
 * @group Secure login
 */
class SecureLoginTestConfig extends SecureLoginTestBase {

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp() {
    parent::setUp();
    // We cannot login to HTTP site if Secure Login is installed.
    if (!$this->isSecure) {
      $this->config('securelogin.settings')->set('base_url', 'https://example.org')->save();
      return;
    }
    $this->web_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->web_user);
    $this->drupalGet('admin/config/people/securelogin');
    $fields['base_url'] = 'https://example.org';
    $this->drupalPostForm(NULL, $fields, t('Save configuration'));
  }

  /**
   * Ensure redirects use the configured base URL.
   */
  protected function testSecureLoginBaseUrl() {
    // Disable redirect following.
    $maximum_redirects = $this->maximumRedirects;
    $this->maximumRedirects = 0;
    $this->drupalGet($this->httpUrl('user/login'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical($headers[0]['location'], 'https://example.org/user/login', 'Location header uses the configured secure base URL.');
    $this->maximumRedirects = $maximum_redirects;
  }

}
