<?php

namespace Drupal\akismet\Tests;

/**
 * Tests CAPTCHA with enabled form cache.
 *
 * @group akismet
 */
class CaptchaFormCacheTest extends CaptchaTest {
  public function setUp() {
    parent::setUp();
    \Drupal::state()->set('akismet_test.cache_form', TRUE);

    // Prime the form cache.
    $this->drupalGet('akismet-test/form');
    $this->assertText('Views: 0');
    $edit = [
      'title' => $this->randomString(),
      self::CAPTCHA_INPUT => 'correct',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }
}
