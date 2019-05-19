<?php

namespace Drupal\Tests\whitelabel\Functional;

/**
 * Tests theme negotiation with White label.
 *
 * @group whitelabel
 */
class WhiteLabelThemeNegotiatorTest extends WhiteLabelTestBase {

  /**
   * The theme manager used in this test.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'whitelabel_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('theme_installer')->install(['seven']);
    $this->themeManager = $this->container->get('theme.manager');

    $this->config('whitelabel.settings')
      ->set('site_name', FALSE)
      ->set('site_name_display', FALSE)
      ->set('site_slogan', FALSE)
      ->set('site_logo', FALSE)
      ->set('site_colors', FALSE)
      ->set('site_theme', FALSE)
      ->set('site_admin_theme', NULL)
      ->save();
  }

  /**
   * Test to see if the admin configured WL theme is applied.
   */
  public function testAdminWhiteLabelTheme() {
    $this->config('whitelabel.settings')
      ->set('site_admin_theme', 'seven')
      ->save();

    $this->whiteLabel
      ->setTheme(NULL)
      ->save();

    $body = $this->drupalGet('<front>');
    $this->assertNotContains('seven', $body);

    // Apply white label.
    $this->setCurrentWhiteLabel($this->whiteLabel);
    $body = $this->drupalGet('<front>');
    $this->assertContains('seven', $body);

    // Remove white label.
    $this->resetWhiteLabel();
    $body = $this->drupalGet('<front>');
    $this->assertNotContains('seven', $body);
  }

  /**
   * Test to see if the WL specific theme is applied.
   */
  public function testWhiteLabelDefinedTheme() {
    $this->config('whitelabel.settings')
      ->set('site_theme', TRUE)
      ->save();

    $this->whiteLabel
      ->setTheme('seven')
      ->save();

    $body = $this->drupalGet('<front>');
    $this->assertNotContains('seven', $body);

    // Apply white label.
    $this->setCurrentWhiteLabel($this->whiteLabel);
    $body = $this->drupalGet('<front>');
    $this->assertContains('seven', $body);

    // Unset white label.
    $this->resetWhiteLabel();
    $body = $this->drupalGet('<front>');
    $this->assertNotContains('seven', $body);
  }

  /**
   * Test to see if no white label is applied if site_theme is FALSE.
   */
  public function testWhiteLabelDefinedThemeNotEnabled() {
    $this->config('whitelabel.settings')
      ->set('site_theme', FALSE)
      ->save();

    $this->whiteLabel
      ->setTheme('seven')
      ->save();

    // Apply white label.
    $this->setCurrentWhiteLabel($this->whiteLabel);
    $body = $this->drupalGet('<front>');
    // Make sure it does NOT contain any references to seven.
    $this->assertNotContains('seven', $body);
  }

}
