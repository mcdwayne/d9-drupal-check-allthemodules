<?php

namespace Drupal\addanother\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Addanother functionality.
 *
 * @group Addanother
 */
class AddAnotherTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static public $modules = array('addanother');

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Tests Database Logging module functionality through interfaces.
   *
   * First creates content type, then logs in users, then creates nodes,
   * and finally tests Addanother module functionality through user interface.
   */
  public function testAddanother() {
    $node_type = $this->randomMachineName(8);
    $config = \Drupal::service('config.factory')->getEditable('addanother.settings');
    $config
      ->set('button.' . $node_type, TRUE)
      ->set('message.' . $node_type, TRUE)
      ->set('tab.' . $node_type, TRUE)
      ->set('tab_edit.' . $node_type, TRUE)
      ->save();

    $settings = array(
      'type' => $node_type,
      'name' => $node_type,
    );
    $this->drupalCreateContentType($settings);

    $web_user = $this->drupalCreateUser(array(
      'bypass node access',
      'administer content types',
      'use add another',
      'administer add another',
    ));
    $this->drupalLogin($web_user);

    // Create a node.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm("node/add/$node_type", $edit, t('Save'));

    // Check that the node has been created.
    $this->assertText(t('@post @title has been created.', array(
      '@post' => $node_type,
      '@title' => $edit['title[0][value]'],
    )), 'Node created.');
    $this->assertText(t('You may add another @type.', array('@type' => $node_type)), 'Addanother message was presented.');
    $this->assertLink('Add another');

    // Create a node.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm("node/add/$node_type", $edit, t('Save and add another'));

    // Check that the node has been created.
    $this->assertUrl("node/add/$node_type");
  }

}
