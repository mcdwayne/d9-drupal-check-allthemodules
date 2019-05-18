<?php

namespace Drupal\akismet\Tests;

/**
 * Tests CAPTCHA as authenticated user.
 *
 * @group akismet
 */
class CaptchaAuthenticatedTest extends CaptchaTest {
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([]));
  }
}
