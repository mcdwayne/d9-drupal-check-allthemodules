<?php

namespace Drupal\Tests\paragraphs_toolbar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests devel toolbar module functionality.
 *
 * @group devel
 */
class ParagraphsToolbarTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'paragraphs',
    'toolbar',
    'admin_toolbar',
    'paragraphs_toolbar',
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

    $this->paragraphsUser = $this->drupalCreateUser([
      'administer site configuration',
      'administer paragraphs types',
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
    $library_css_url = 'paragraphs_toolbar/css/paragraphs.toolbar.css';
    $toolbar_selector = '#toolbar-bar .toolbar-tab';
    $toolbar_tab_selector = '#toolbar-bar .toolbar-tab a.toolbar-icon-paragraphs';
    $toolbar_tray_selector = '#toolbar-bar .toolbar-tab #toolbar-item-paragraphs-tray';

    // Ensures that paragraphs toolbar item is accessible only for user with the
    // adequate permissions.
    $this->drupalGet('');
    $this->assertSession()->responseNotContains($library_css_url);
    $this->assertSession()->elementNotExists('css', $toolbar_selector);
    $this->assertSession()->elementNotExists('css', $toolbar_tab_selector);

    $this->drupalLogin($this->toolbarUser);
    $this->assertSession()->responseNotContains($library_css_url);
    $this->assertSession()->elementExists('css', $toolbar_selector);
    $this->assertSession()->elementNotExists('css', $toolbar_tab_selector);

    $this->drupalLogin($this->paragraphsUser);
    $this->assertSession()->responseContains($library_css_url);
    $this->assertSession()->elementExists('css', $toolbar_selector);
    $this->assertSession()->elementExists('css', $toolbar_tab_selector);
    $this->assertSession()->elementTextContains('css', $toolbar_tab_selector, 'Paragraph Types');

    $this->assertSession()->elementExists('css', $toolbar_tray_selector);

  }

}
