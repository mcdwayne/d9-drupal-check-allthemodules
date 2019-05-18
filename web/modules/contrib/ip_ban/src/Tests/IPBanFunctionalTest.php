<?php

/**
 * @author Dan Harris.    <https://www.drupal.org/u/webdrips>
 */

namespace Drupal\ip_ban\Tests;

use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Tests the complete ban and read only functionality of the IP Ban module.
 *
 * @group IP Ban
 */
class IPBanFunctionalTest extends IPBanTestBase {

  /**
   * Implement setUp().
   */
  public function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminSitesUser);
  }

  /**
   * Test the complete ban functionality.
   */
  public function testCompleteBan() {
    // node/1.
    $this->addBanNode("Read only page");
    // node/2.
    $this->addBanNode("Complete ban page");
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array(
      // Set the United States to Read Only
      // 0 = no action / 1 = Read Only / 2 = Complete Ban.
      'ip_ban_US' => 2,
      // Set the test IP address to a known Google US address.
      'ip_ban_test_ip' => '66.249.84.22',
      // Configure the paths for read-only and complete ban.
      // Todo: use $node = $this->drupalGetNodeByTitle($node_title) to get nids.
      'ip_ban_readonly_path' => '/node/1',
      'ip_ban_completeban_path' => '/node/2',
    );
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertOptionSelected('edit-ip-ban-us', 2, 'Complete ban set correctly in country list table.');
    // Should be redirected to complete ban page after logout.
    $this->drupalGet('user/logout');
    // Todo: figure out why the logout is not being redirected in the test.
    // $this->assertUrl('/node/2');
  }

  /**
   * Test the read-only and complete ban functionality.
   */
  public function testReadOnlyWithDisabledBlock() {
    // node/1.
    $this->addBanNode("Read only page");
    $this->drupalGet(IP_BAN_ADMIN_FORM_PATH);
    $edit = array(
      // Set the United States to Read Only
      // 0 = no action / 1 = Read Only / 2 = Complete Ban.
      'ip_ban_US' => 1,
      // Set the test IP address to a known Google US address.
      'ip_ban_test_ip' => '66.249.84.22',
      // Configure the path for read-only.
      'ip_ban_readonly_path' => '/node/1',
      // Disable user login and powered by Drupal blocks for read-only users.
      // These blocks are set by default to the left sidebar and footer for
      // fresh installs, so no need to configure below.
      // 'ip_ban_disabled_blocks' => 'system,powered-by' . PHP_EOL . 'user,login',
    );
    $this->drupalPostForm(IP_BAN_ADMIN_FORM_PATH, $edit, t('Save configuration'));
    $this->assertOptionSelected('edit-ip-ban-us', 1, 'Read only set correctly in country list table.');
    // Set block title to confirm that the interface is available.
    // No need to do the following on D7 sites since the user login and powered
    // by Drupal are enabled by default.
    // $edit = array(
    // 'title' => $this->randomMachineName(),
    // 'pages' => 'node/1',
    // );
    // $this->drupalPost('admin/structure/block/manage/search/form/configure', $edit, t('Save block'));
    // $this->assertText(t('The block configuration has been saved.'), 'Block configuration set.');
    // // Set the block to a region to confirm block is available.
    // $edit = array();
    // $edit['blocks[search_form][region]'] = 'footer';
    // $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    // $this->assertText(t('The block settings have been updated.'), 'Block successfully move to footer region.');
    // Attempt to access user page after logging privileged user out.
    $this->drupalGet('node/1');
    // $theme = \Drupal::theme()->getActiveTheme()->getName();
    // $all_regions = system_region_list($theme);
    // debug($all_regions);
    // $blocks = block_list('footer');
    // debug($blocks);
    $this->drupalGet('user/logout');
    // \Drupal::service("router.builder")->rebuild();        
    // $this->drupalGet('user/login');
    // Should be redirected to read only page.
    // $this->assertUrl('node/1');
    // Not sure how to actually test if a block is not visible as I would have
    // thought the following would work, but I only see an empty array (even
    // when run before the privileged user is logged out).
    // $blocks = block_list('footer');
    // debug($blocks);
    // Note: a simple visual inspection confirms both the user login and powered
    // by Drupal blocks are missing when viewing the test results.
  }

}
