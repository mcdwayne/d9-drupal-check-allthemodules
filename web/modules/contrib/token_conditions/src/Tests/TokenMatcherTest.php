<?php

/**
 * @file
 * Contains \Drupal\token_conditions\Tests\TokenMatcherTest.
 */

namespace Drupal\token_conditions\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the 'token_matcher' condition plugin.
 *
 * @group token_conditions
 */
class TokenMatcherTest extends WebTestBase {

  /**
   * An administrative user to configure the blocks.
   */
  protected $adminUser;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node1;

  /**
   * The node object.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node2;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'node', 'token_conditions'];

  protected function setUp() {
    parent::setUp();
    // Get the token service.
    $this->tokenService = \Drupal::token();

    // Create and log in as a user who can administer blocks.
    $this->adminUser = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($this->adminUser);

    // Create node instances.
    $node_type = strtolower($this->randomMachineName(8));
    $this->drupalCreateContentType(['type' => $node_type]);
    $this->node1 = $this->drupalCreateNode(['type' => $node_type]);
    $this->node2 = $this->drupalCreateNode(['type' => $node_type]);
  }

  /**
   * Tests the token matcher plugin for node title.
   */
  function testTokenMatcherNodeTitle() {
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme);
    // Check for token matcher field.
    $this->assertField('visibility[token_matcher][token_match]', 'Token matcher field is available.');
    // Check for token matcher field.
    $this->assertField('visibility[token_matcher][value_match]', 'Token value field is available.');

    // Enable a standard block and set the visibility setting for node title.
    $edit = [
      'visibility[token_matcher][token_match]' => '[node:title]',
      'visibility[token_matcher][value_match]' => $this->node1->getTitle(),
      'id' => strtolower($this->randomMachineName(8)),
      'region' => 'sidebar_first',
    ];
    $this->drupalPostForm('admin/structure/block/add/system_powered_by_block' . '/' . $default_theme, $edit, t('Save block'));

    $this->drupalGet('node/' . $this->node1->id());
    $this->assertText('Powered by Drupal', 'The block appears on the page.');

    $this->drupalGet('node/' . $this->node2->id());
    $this->assertNoText('Powered by Drupal', 'The block does not appear on the page.');
  }

}
