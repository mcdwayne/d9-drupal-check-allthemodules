<?php

/**
 * @file
 * Contains \Drupal\freshdesk_sso\Tests\RedirectController.
 */

namespace Drupal\freshdesk_sso\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\freshdesk_sso\AuthenticationService;

/**
 * Provides automated tests for the freshdesk_sso module.
 */
class RedirectControllerTest extends WebTestBase {

  /**
   * Drupal\freshdesk_sso\AuthenticationService definition.
   *
   * @var Drupal\freshdesk_sso\AuthenticationService
   */
  protected $freshdesk_sso_authentication;
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "freshdesk_sso RedirectController's controller functionality",
      'description' => 'Test Unit for module freshdesk_sso and controller RedirectController.',
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
   * Tests freshdesk_sso functionality.
   */
  public function testRedirectController() {
    // Check that the basic functions of module freshdesk_sso.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
