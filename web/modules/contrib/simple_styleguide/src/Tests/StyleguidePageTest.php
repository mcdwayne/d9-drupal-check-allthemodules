<?php

namespace Drupal\simple_styleguide\Tests;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group simple_styleguide
 */
class StyleguidePageTest extends BrowserTestBase {

  /**
   * Strict config schema check.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simple_styleguide'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration', 'access style guide']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the style guide page loads with a 200 response without config.
   */
  public function testStyleGuidePageEmpty() {
    $this->drupalGet(Url::fromRoute('simple_styleguide.controller'));
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the no styleguide patterns text is visible.
    $no_styleguide_patterns_text = $this->xpath($this->cssSelectToXpath('.simple-styleguide--site-styles .site-styles--shortcuts > p'));
    $this->assertEquals('You have not selected or created any styleguide patterns.', $no_styleguide_patterns_text[0]->getText());

    // Assert that the configure link is visible.
    $configure_link = Url::fromRoute('simple_styleguide.styleguide_settings')->toString();
    $this->assertSession()->linkExists('Configure Simple Styleguide');
    $this->assertSession()->linkByHrefExists($configure_link);
  }

  /**
   * Tests that the style guide page loads with some config.
   */
  public function testStyleGuidePageFunctional() {
    $simple_style_guide_config = [
      'default_patterns[headings]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/styleguide/settings', $simple_style_guide_config, t('Save'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->checkboxChecked('default_patterns[headings]');

    $this->drupalGet(Url::fromRoute('simple_styleguide.controller'));
    $this->assertSession()->statusCodeEquals(200);

    $styleguide_section_headings = $this->xpath($this->cssSelectToXpath('.simple-styleguide--site-styles .sections > h3'));
    $this->assertEquals('headings', $styleguide_section_headings[0]->getText());
  }

}
