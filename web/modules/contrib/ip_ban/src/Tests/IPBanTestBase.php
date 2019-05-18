<?php

/**
 * @author Dan Harris.    <https://www.drupal.org/u/webdrips>
 */

namespace Drupal\ip_ban\Tests;
use Drupal\simpletest\WebTestBase;

// The form configuration URI
define('IP_BAN_ADMIN_FORM_PATH', 'admin/config/ip_ban');

/**
 * IP Ban Admin form tests.
 *
 * @group IP Ban
 */
abstract class IPBanTestBase extends WebTestBase {

  // protected $profile = 'standard';
  protected $adminSitesUser;
  // protected $ipBanType;
  
  /**
   * Modules to enable.
   *
   * @var array
   *
   * ip2country is required by ip_ban, but should be installed by default since
   * it's a dependent module.
   */
  public static $modules = array('block', 'node', 'ip2country', 'ip_ban');
  
  // Since we can't really know the schema for the list of countries (since it
  // can different from one week to the next), we'll disable the checking for 
  // valid configuration schema on save. 
  // Todo: try to figure out a workaround.
  // see https://www.drupal.org/node/2666196
  protected $strictConfigSchema = FALSE;  

  /**
   * Implement setUp().
   */
  public function setUp() {
    parent::setUp();
    // Add a content type to create dummy page for testing valid paths and
    // proper redirects.
    $this->ipBanType = $this->drupalCreateContentType([
      'name' => 'IP Ban Node',
      'type' => 'ipban_node',
    ]);
    // Create and log in our privileged user.
    $this->adminSitesUser = $this->drupalCreateUser(array(
      'administer site configuration',
      // 'access administration pages',
      'create ipban_node content',
      'edit any ipban_node content',
      'access content',
      // 'bypass node access',
      'administer blocks',
      // The admin must have this permission to avoid immediately losing access
      // when using a spoofed IP address.
      'ignore ip_ban',
    ));
    // $this->drupalLogin($this->adminSitesUser);    
  }

  /**
   * Add a node of type ipban-node for path and other testing.
   */
  public function addBanNode($title = '') {
    if (empty($title)) {
      $title = $this->randomMachineName();
    }
    $edit = array();
    $edit['title[0][value]'] = $title;
    $this->drupalGet('node/add/ipban_node');
    $this->drupalPostForm('node/add/ipban_node', $edit, t('Save'));
    $this->assertText('IP Ban Node ' . $edit['title[0][value]'] . ' has been created', 'Found node creation message');
  }

}
