<?php

namespace Drupal\akismet\Tests;

/**
 * Tests CAPTCHA as authenticated user with enabled form cache.
 *
 * @group akismet
 */
class CaptchaAuthenticatedFormCacheTest extends CaptchaFormCacheTest {
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateuser([]));
  }
}
