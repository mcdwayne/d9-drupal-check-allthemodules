<?php

namespace Drupal\cloudwords\Tests;

use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

abstract class CloudwordsTestBase extends WebTestBase {

  /*
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['cloudwords', 'cloudwords_translation'];

  protected function setUp() {
    parent::setUp();

    // Login as admin.
    $this->drupalLogin($this->rootUser);

  }

}
