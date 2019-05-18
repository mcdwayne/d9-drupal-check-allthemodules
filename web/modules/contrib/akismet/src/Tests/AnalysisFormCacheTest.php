<?php

namespace Drupal\akismet\Tests;

/**
 * Tests text analysis with enabled form cache.
 * @group akismet
 */
class AnalysisFormCacheTest extends AnalysisTest {

  public function setUp() {
    parent::setUp();
    \Drupal::state()->set('akismet_test.cache_form', TRUE);

    // Prime the form cache.
    $this->drupalGet('akismet-test/form');
    $this->assertText('Views: 0');
    $edit = [
      'title' => $this->randomString()
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
  }
}
