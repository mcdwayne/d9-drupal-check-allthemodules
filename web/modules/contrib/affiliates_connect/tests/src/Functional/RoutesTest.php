<?php

namespace Drupal\Tests\affiliates_connect\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Check if our defined routes are working correctly or not.
 *
 * @group affiliates_connect
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RoutesTest extends BrowserTestBase {

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user without admin permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * The permissions of the admin user.
   *
   * @var string[]
   */
  protected $adminUserPermissions = [
    'administer affiliates product entities',
    'add affiliates product entities',
    'delete affiliates product entities',
    'edit affiliates product entities',
    'view published affiliates product entities',
    'view unpublished affiliates product entities',
    'access administration pages',
  ];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['affiliates_connect'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->adminUserPermissions);
    $this->unauthorizedUser = $this->drupalCreateUser();
  }

  /**
   * Test that the availability of affiliates_connect.overview route.
   */
  public function testOverviewRoutes() {
    // For admin
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(URL::fromRoute('affiliates_connect.overview'));
    $this->assertResponse(200);

    // For user without admin permissions
    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet(URL::fromRoute('affiliates_connect.overview'));
    $this->assertResponse(403);
  }

  /**
   * Test that the availability of affiliates_connect.admin_config route.
   */
  public function testMenuBlockRoutes() {
    // For admin
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(URL::fromRoute('affiliates_connect.admin_config'));
    $this->assertResponse(200);

    // For user without admin permissions
    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet(URL::fromRoute('affiliates_connect.admin_config'));
    $this->assertResponse(403);
  }

  /**
   * Test that the availability of affiliates_product entity routes.
   */
  public function testAffiliatesProductEntityRoutes() {
    // For admin
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(URL::fromRoute('entity.affiliates_product.collection'));
    $this->assertResponse(200);

    // For user without admin permissions
    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet(URL::fromRoute('entity.affiliates_product.collection'));
    $this->assertResponse(403);
  }
}
