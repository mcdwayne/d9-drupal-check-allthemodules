<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Drupal\Tests\role_based_theme_switcher\Functional;

use Drupal\Tests\BrowserTestBase;
/**
 * Description of RoleBasedPathTest
 *
 * @author pen
 */
class RoleBasedPathTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['role_based_theme_switcher'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
  * Tests that the Role Based UI pages are reachable.
  *
  * @group role_based_theme_switcher
  */
  public function testReachableRulePage() {
    $values = [
      'edit-role-theme-authenticated-id' => 'stable'
    ];
    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/structure/role_based_theme_switcher/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Role Based Theme Setting');
    $this->submitForm($values, 'Save configuration', 'role-admin-settings');
    $xpath="//div[@class='messages messages--status']";
    $this->xpath($xpath);
  }
}