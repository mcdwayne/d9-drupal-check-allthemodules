<?php

namespace Drupal\contactlist\Tests;

use Drupal\contactlist\Entity\ContactListEntry;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests admin settings form.
 *
 * @group ContactListEntry
 */
class AdminSettingsFormTest extends WebTestBase {

  protected $profile = 'testing';

  protected static $modules = ['contactlist'];

  /**
   * Verifies that the "manage fields", etc. tabs are showing.
   */
  public function testManageFieldsTabs() {
    $this->container->get('module_installer')->install(['block', 'field_ui']);
    // Create test entities for the user1 and unrelated to a user.
    $this->drupalPlaceBlock('local_tasks_block', [
      'region' => 'content',
      'weight' => -1
    ]);

    // Confirm the admin settings page has the manage fields, etc. tabs.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(new Url('contactlist.admin_form'));
    $this->assertLink('Manage fields');
    $this->assertLink('Manage form display');
    $this->assertLink('Manage display');
  }

  /**
   * Tests the default settings on the admin settings form.
   */
  public function testDefaultSettings() {
    // Create test entities for the user1 and unrelated to a user.
    $user = $this->drupalCreateUser(['administer contact lists']);
    $this->drupalLogin($user);

    // Confirm the admin settings page default settings
    $this->drupalGet(new Url('contactlist.admin_form'));
    $config = $this->config('contactlist.settings');
    $this->assertFieldByXPath('//select[@name="name_field"]', $config->get('name_field'));
    $this->assertFieldByXPath('//input[@name="label_format"]', $config->get('label_format'));
    $this->assertFieldByXPath('//select[@name="group_field"]', $config->get('group_field'));
    $this->assertFieldByXPath('//select[@name="default_field"]', $config->get('default_field'));
    $this->assertFieldByXPath('//input[@name="expose_default_field"]', $config->get('expose_default_field'));
    $this->assertFieldByXPath('//input[@name="quick_import_parse_rule"]', $config->get('quick_import_parse_rule'));
    $this->assertFieldByXPath('//input[@name="field_mapping[name]"]', $config->get('field_mapping.name'));
    $this->assertFieldByXPath('//input[@name="field_mapping[email]"]', $config->get('field_mapping.email'));
    $this->assertFieldByXPath('//input[@name="field_mapping[telephone]"]', $config->get('field_mapping.telephone'));
    foreach ($config->get('unique_fields') as $field_name) {
      $this->assertFieldByXPath('//input[@name="unique_fields[' . $field_name . ']"]', $field_name);
    }

  }

  /**
   * Test that a user cannot see other user's contacts.
   */
  public function testAdminSettingsModification() {

  }

}
