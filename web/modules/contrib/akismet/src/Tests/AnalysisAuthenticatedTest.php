<?php

namespace Drupal\akismet\Tests;

/**
 * Tests text analysis as authenticated user.
 * @group akismet
 */
class AnalysisAuthenticatedTest extends AnalysisTest {
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([]));
  }
}
