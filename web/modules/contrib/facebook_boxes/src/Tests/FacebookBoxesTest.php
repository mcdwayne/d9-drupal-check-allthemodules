<?php

/**
 * @file
 */

namespace Drupal\facebook_boxes\Tests;

use Drupal\simpletest\WebTestBase;

class FacebookBoxesTest extends WebTestBase {
  public static $modules = array('block', 'facebook_boxes');
  protected $web_user;

  public static function getInfo() {
    return array(
      'name' => 'Facebook Boxes Tests',
      'description' => 'tests facebook boxes output',
      'group' => 'Other',
    );
  }

  function setUp() {
    parent::setUp();
  }

  // OK now add tests you shcmuck.
}