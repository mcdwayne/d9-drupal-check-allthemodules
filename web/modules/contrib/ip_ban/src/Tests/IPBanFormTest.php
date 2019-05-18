<?php

/**
 * @author Dan Harris.    <https://www.drupal.org/u/webdrips>
 */
 
namespace Drupal\ip_ban\Tests;

/**
 * Tests the IP Ban admin page form.
 *
 * @group IP Ban
 */
class IPBanFormTest extends IPBanTestBase {
  

  /**
   * Implement setUp().
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminSitesUser);
  }

  /**
   * Various tests for paths entered in the the admin form.
   */
  public function testBanPaths() {
    // Add node for testing path and making sure we land on the proper page
    // for read-only and complete ban paths.
    $this->addBanNode("Test page");
    // Test the read only path saves correctly for valid path.
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array();
    $this->assertResponse(200, 'IP Ban admin form exists.');
    $this->assertFieldById('edit-submit');
    $edit['ip_ban_readonly_path'] = '/node/1';
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/ip_ban.settings.yml and config/schema/ip_ban.schema.yml.
    $readOnlyPath = \Drupal::config('ip_ban.settings')->get('ip_ban_readonly_path');
    $this->assertIdentical($readOnlyPath, '/node/1');

    // // Test the read only path returns an error on invalid path.
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array();
    $edit['ip_ban_readonly_path'] = '/readonly_path_' . $this->randomMachineName(16);
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertText(t('The path entered does not exist or you do not have permission to access it.'));

    // // Test the complete ban path returns an error on invalid path.
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array();
    $edit['ip_ban_completeban_path'] = 'node/1';
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertText(t('The path must start with a forward slash (/).'));
  }

  /**
   * Test setting setting the US within the table.
   */
  public function testSetCountry() {
    // Set the United States to Complete Ban.
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array();
    // 0 = no action / 1 = Read Only / 2 = Complete Ban.
    $edit['ip_ban_US'] = 2;
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertOptionSelected('edit-ip-ban-us', 2, 'Complete ban set correctly in country list table.');
  }

  /**
   * Test adding IP address in various fields (correct and incorrect).
   */
  public function testIpAddressEntry() {
    // We need to have one test page on the site.
    $this->addBanNode("Test page");
    // Add multiple valid IP addresses.
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array(
      'ip_ban_readonly_path' => '/node/1',
      'ip_ban_readonly_ips'  => '192.168.32.60' . PHP_EOL . '156.228.60.110 ',
    );
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    // Try to add an invalid IP address.
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array();
    $edit['ip_ban_additional_ips'] = '666.666.666.666';
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertText(t('You have entered one or more incorrect IPV4 addresses.'));

    // Add multiple IP addresses on the same line (error)
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array();
    $edit['ip_ban_additional_ips'] = '192.168.32.60, 156.228.60.110';
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertText(t('You have entered one or more incorrect IPV4 addresses.'));
  }

  /**
   * Test adding multiple disabled blocks (correct and incorrect).
   */
  // public function testDisabledBlockEntry() {
    // // We need to have one test page on the site.
    // $this->addBanNode("Test page");
    // // Add a valid block.
    // $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    // $edit = array(
    //   'ip_ban_disabled_blocks' => 'user,login',
    //   'ip_ban_readonly_path' => '/node/1',
    // );
    // $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    // $this->assertText(t('The configuration options have been saved.'));

    // // Use incorrect formatting for the blocks.
    // $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    // $edit = array();
    // $edit['ip_ban_disabled_blocks'] = 'user, login block,11';
    // $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    // $this->assertText(t('You have one or more blocks with an incorrect format; you must enter exactly one module name and delta name per line, separated by a comma.'));

    // // Add a block with an incorrect delta.
    // $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    // $edit = array();
    // $edit['ip_ban_disabled_blocks'] = 'user,login2';
    // $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    // $this->assertText(t('You entered at least one invalid module name or delta; see the help text for how to enter the proper module name and delta.'));
  // }

}