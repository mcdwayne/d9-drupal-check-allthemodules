<?php

namespace Drupal\Tests\composer_security_checker\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class Requirements.
 *
 * @package Drupal\Tests\composer_security_checker\Functional
 *
 * @group composer_security_checker
 */
class Requirements extends BrowserTestBase {

  public static $modules = [
    'composer_security_checker',
  ];

  /**
   * A normal authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'administer site configuration',
      'access administration pages',
      'access site reports',
    ];
    $web_user = $this->drupalCreateUser($permissions);

    $this->drupalLogin($web_user);
    $this->webUser = $web_user;
  }

  /**
   * Test that the requirements page shows that the class exists.
   */
  public function testClassRequirementsWhenClassExists() {

    $this->drupalGet('admin/reports/status');
    $this->assertSession()
      ->pageTextContains('Composer Security Checker service available');

  }

}
