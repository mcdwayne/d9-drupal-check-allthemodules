<?php

namespace Drupal\Tests\ext_redirect\Functional;

/**
 * Created by PhpStorm.
 * User: marek.kisiel
 * Date: 22/08/2017
 * Time: 10:52
 */

/**
 * Class ExtRedirectRuleFormWebTest
 * @group ext_redirect
 */
class ExtRedirectRuleFormTest extends ExtRedirectWebTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  function testAddRedirectRule() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/search/redirect_rule');
    $this->assertResponse(200);
    $this->assertUrl('admin/config/search/redirect_rule');
    // @TODO figure out why add link is not available.
    $this->clickLink('Add Redirect Rule');
    $this->assertUrl('admin/config/search/redirect_rule/add');
    $this->assertResponse(200);
  }

}