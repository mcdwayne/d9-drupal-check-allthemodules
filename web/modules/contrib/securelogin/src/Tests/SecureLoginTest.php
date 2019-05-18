<?php

namespace Drupal\securelogin\Tests;

/**
 * Basic tests for Secure login module.
 *
 * @group Secure login
 */
class SecureLoginTest extends SecureLoginTestBase {

  /**
   * Ensure a request over HTTP gets 301 redirected to HTTPS.
   */
  protected function testHttpSecureLogin() {
    global $base_url;
    // Disable redirect following.
    $maximum_redirects = $this->maximumRedirects;
    $this->maximumRedirects = 0;
    $this->drupalGet($this->httpUrl('user/login'));
    $headers = $this->drupalGetHeaders(TRUE);
    $this->assertTrue(strpos($headers[0][':status'], '301'), 'Status header contains 301.');
    $this->assertIdentical(0, strpos($headers[0]['location'], str_replace('http://', 'https://', $base_url)), 'Location header uses the secure base URL.');
    $this->maximumRedirects = $maximum_redirects;
  }

  /**
   * Ensure HTTPS requests do not get redirected.
   */
  protected function testHttpsSecureLogin() {
    $this->drupalGet($this->httpsUrl('user/login'));
    $this->assertResponse(200);

    $xpath = $this->xpath('//form[@id="user-login-form"]');
    $this->assertEqual(count($xpath), 1, 'The user is on the login form.');
  }

}
