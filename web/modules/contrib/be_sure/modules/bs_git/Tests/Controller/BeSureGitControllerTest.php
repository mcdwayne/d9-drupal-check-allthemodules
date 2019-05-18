<?php

namespace Drupal\bs_git\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the bs_git module.
 */
class BeSureGitControllerTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => "bs_git BeSureGitController's controller functionality",
      'description' => 'Test Unit for module bs_git and controller BeSureGitController.',
      'group' => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests bs_git functionality.
   */
  public function testBeSureGitController() {
    // Check that the basic functions of module bs_git.
    $this->assertEqual(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
