<?php

namespace Drupal\Tests\role_paywall\Functional;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\role_paywall\Functional\RolePaywallTestBase;

/**
 * Test for the Role paywall administrative interface.
 *
 * @group role_paywall
 */
class RolePaywallAdminTest extends RolePaywallTestBase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests the configuration page access.
   */
  public function testConfigPageAccess() {
    $this->drupalGet('/admin/config/content/role_paywall');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/role_paywall');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the configuration page elements.
   */
  public function testConfigPageElements() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/role_paywall');

    $this->assertSession()->elementExists('css', 'input[value=anonymous]');
    $this->assertSession()->elementExists('css', 'input[value=authenticated]');
    $this->assertSession()->elementsCount('css', 'fieldset[data-drupal-selector=edit-roles] input', 3);
    $this->assertSession()->elementExists('css', 'input[value=article]');
    $this->assertSession()->fieldValueEquals('roles[authenticated]', NULL);
    $this->assertSession()->fieldValueEquals('roles[anonymous]', NULL);
    $this->assertSession()->fieldValueEquals('roles[' . $this->adminRole . ']', NULL);
    $this->assertSession()->fieldValueEquals('bundles[article]', NULL);
    $this->assertSession()->selectExists('barrier_block');
  }

  /**
   * Tests configuration is properly saved.
   */
  public function testConfigPageSave() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/role_paywall');

    $edit = [];
    $edit['roles[' . $this->adminRole . ']'] = TRUE;
    $edit['bundles[article]'] = TRUE;
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Checks that role and bundle have been saved and loaded the proper
    // default value.
    $this->assertSession()->elementExists('css', 'input[value=' . $this->adminRole . '][checked=checked]');
    $this->assertSession()->elementExists('css', 'input[value=article][checked=checked]');

    // Checks new fields appears based on the bundle selection.
    $this->assertSession()->fieldValueEquals('activate_paywall_field_article', NULL);
    $this->assertSession()->fieldValueEquals('hidden_fields_article[' . $this->premiumFieldName . ']', NULL);

    // Set up the proper config and check the form is loaded correctly.
    $this->setConfig();
    $this->drupalGet('/admin/config/content/role_paywall');

    // Checks all configurations are saved and loaded.
    $this->assertSession()->elementExists('css', 'input[value=' . $this->adminRole . '][checked=checked]');
    $this->assertSession()->elementExists('css', 'input[value=article][checked=checked]');
    $this->assertSession()->elementExists('css', 'input[value=' . $this->premiumFieldName . '][checked=checked]');
    $this->assertSession()->elementExists('css', 'input[value=' . $this->activateFieldName . '][checked=checked]');
  }
}
