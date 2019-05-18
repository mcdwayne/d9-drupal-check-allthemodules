<?php

namespace Drupal\Tests\mfd\Functional;

use Drupal\simpletest\WebTestBase;

/**
 * Provides a base class for testing the Path module.
 */
abstract class MultilingualFormTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node'];

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      //      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
  }

}


