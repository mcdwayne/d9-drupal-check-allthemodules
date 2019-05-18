<?php

namespace Drupal\akismet\Tests;

/**
 * Tests text analysis of an authenticated user with enabled form cache.
 * @group akismet
 */
class AnalysisAuthenticatedFormCacheTest extends AnalysisFormCacheTest {

  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([]));
  }
}
