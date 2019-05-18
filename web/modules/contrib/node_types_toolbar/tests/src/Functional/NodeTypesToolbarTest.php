<?php

namespace Drupal\Tests\node_types_toolbar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests devel toolbar module functionality.
 *
 * @group devel
 */
class NodeTypesToolbarTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'toolbar',
    'admin_toolbar',
    'node_types_toolbar',
  ];

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $toolbarUser;

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer content types',
      'access toolbar',
    ]);
    $this->toolbarUser = $this->drupalCreateUser([
      'access toolbar',
    ]);
  }

  /**
   * Tests toolbar integration.
   */
  public function testToolbarIntegration() {
    $library_css_url = 'node_types_toolbar/css/node-types.toolbar.css';
    $toolbar_selector = '#toolbar-bar .toolbar-tab';
    $toolbar_tab_selector = '#toolbar-bar .toolbar-tab a.toolbar-icon-node-types';
    $toolbar_tray_selector = '#toolbar-bar .toolbar-tab #toolbar-item-node-types-tray';

    // Ensures that node types toolbar item is accessible only for user with the
    // adequate permissions.
    $this->drupalGet('');
    $this->assertSession()->responseNotContains($library_css_url);
    $this->assertSession()->elementNotExists('css', $toolbar_selector);
    $this->assertSession()->elementNotExists('css', $toolbar_tab_selector);

    $this->drupalLogin($this->toolbarUser);
    $this->assertSession()->responseNotContains($library_css_url);
    $this->assertSession()->elementExists('css', $toolbar_selector);
    $this->assertSession()->elementNotExists('css', $toolbar_tab_selector);

    $this->drupalLogin($this->adminUser);
    $this->assertSession()->responseContains($library_css_url);
    $this->assertSession()->elementExists('css', $toolbar_selector);
    $this->assertSession()->elementExists('css', $toolbar_tab_selector);
    $this->assertSession()->elementTextContains('css', $toolbar_tab_selector, 'Node Types');

    $this->assertSession()->elementExists('css', $toolbar_tray_selector);

  }

}
