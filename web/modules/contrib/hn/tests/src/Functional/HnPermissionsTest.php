<?php

namespace Drupal\Tests\hn\Functional;

use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides some basic tests with permissions of the HN module.
 *
 * @group hn
 */
class HnPermissionsTest extends HnFunctionalTestBase {

  public static $modules = [
    'hn_test',
  ];

  /**
   * Tests the response if the user doesn't have the 'access hn' permission.
   *
   * If the 'access hn' permission isn't enabled, the endpoint should return a
   * 403 status.
   */
  public function testWithoutRestPermission() {
    /** @var \Drupal\user\Entity\Role $anonymous */
    $anonymous = Role::load(RoleInterface::ANONYMOUS_ID);
    $anonymous->revokePermission('access hn');
    $anonymous->save();
    $this->getHnResponse('/node/1');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the response if the user doesn't have the 'content' permission.
   *
   * If the user has access to the 'access hn' but not to 'access content',
   * the status should be 200 but the response should contain the 403 page.
   */
  public function testWithoutContentPermission() {
    /** @var \Drupal\user\Entity\Role $anonymous */
    $anonymous = Role::load(RoleInterface::ANONYMOUS_ID);
    $anonymous->revokePermission('access content');
    $anonymous->trustData()->save();
    $response = $this->getHnJsonResponse('/node/1');

    // @todo: Add 403 and 404 fallback page to site.settings config
    // $this->assertSession()->statusCodeEquals(200);
    // $this->assertEquals($response['data'][
    // $response['paths'][$this->nodeUrl]
    // ]['__hn']['status'], 403);
    $this->assertFalse(isset($response['paths']['/node/1']));
  }

}
