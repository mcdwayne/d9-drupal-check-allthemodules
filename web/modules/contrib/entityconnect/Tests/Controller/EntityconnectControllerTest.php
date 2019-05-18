<?php

namespace Drupal\entityconnect\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides automated tests for the entityconnect module.
 */
class EntityconnectControllerTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "entityconnect EntityconnectController's controller functionality",
      'description' => 'Test Unit for module entityconnect and controller EntityconnectController.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests entityconnect functionality.
   */
  public function testEntityconnectController() {
    // Check that the basic functions of module entityconnect.
    $this->assertEqual(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
