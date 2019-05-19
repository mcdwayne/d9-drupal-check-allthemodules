<?php

namespace Drupal\Tests\whitelabel\Functional;

use Drupal\file\Entity\File;

/**
 * Tests if the form fields and rendered values show and hide as configured.
 *
 * @group whitelabel
 */
class WhiteLabelFeatureToggleTest extends WhiteLabelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'color',
    'entity_reference_revisions',
    'whitelabel_test',
  ];

  /**
   * Array of all config fields that toggle form/page elements.
   *
   * @var array
   */
  private $configDefaults = [
    'site_name' => FALSE,
    'site_name_display' => FALSE,
    'site_slogan' => FALSE,
    'site_logo' => FALSE,
    'site_theme' => FALSE,
    'site_colors' => FALSE,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Make sure everything is disabled by default.
    $this->config('whitelabel.settings')
      ->setData($this->configDefaults)
      ->save();
  }

  /**
   * Test to see if the form displays the right fields.
   */
  public function testForm() {
    // We need a second theme to be installed to have it show up on the form.
    $this->container->get('theme_installer')->install(['bartik']);

    // Remove the logo so we can assert the display of the form field.
    // Enable a theme with color integration.
    $this->whiteLabel
      ->setLogo(File::create())
      ->setTheme('bartik')
      ->save();

    $field_name = 'field_whitelabel';
    $this->attachFieldToEntity('user', 'user', $field_name);

    foreach (array_keys($this->configDefaults) as $config_key) {
      $this->config('whitelabel.settings')
        // Disable all features.
        ->setData($this->configDefaults)
        // Enable the feature we want to test.
        ->set($config_key, TRUE)
        ->save();

      $this->drupalGet('user/' . $this->whiteLabel->getOwnerId() . '/edit');

      // Token should always be visible.
      $this->assertSession()->fieldExists($field_name . '[0][token][0][value]');

      $config_key == 'site_name' ?
        $this->assertSession()->fieldExists($field_name . '[0][name][0][value]') :
        $this->assertSession()->fieldNotExists($field_name . '[0][name][0][value]');

      $config_key == 'site_name_display' ?
        $this->assertSession()->fieldExists($field_name . '[0][name_display][value]') :
        $this->assertSession()->fieldNotExists($field_name . '[0][name_display][value]');

      $config_key == 'site_slogan' ?
        $this->assertSession()->fieldExists($field_name . '[0][slogan][0][value]') :
        $this->assertSession()->fieldNotExists($field_name . '[0][slogan][0][value]');

      $config_key == 'site_logo' ?
        $this->assertSession()->fieldExists('files[' . $field_name . '_0_logo_0]') :
        $this->assertSession()->fieldNotExists('files[' . $field_name . '_0_logo_0]');

      $config_key == 'site_theme' ?
        $this->assertSession()->fieldExists($field_name . '[0][theme]') :
        $this->assertSession()->fieldNotExists($field_name . '[0][theme]');

      $config_key == 'site_colors' ?
        $this->assertSession()->elementExists('css', '#color_scheme_form') :
        $this->assertSession()->elementNotExists('css', '#color_scheme_form');
    }
  }

  /**
   * Test to see if the page displays the right values.
   *
   * Application of themes and color schemes have their own tests.
   *
   * @see \Drupal\Tests\whitelabel\Functional\WhiteLabelStyleSheetTest
   * @see \Drupal\Tests\whitelabel\Functional\WhiteLabelThemeNegotiatorTest
   */
  public function testPage() {
    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', [
      'region' => 'header',
    ]);

    // Set a white label.
    $this->setCurrentWhiteLabel($this->whiteLabel);

    foreach (array_keys($this->configDefaults) as $config_key) {
      $this->config('whitelabel.settings')
        // Disable all features.
        ->setData($this->configDefaults)
        // Enable the feature we want to test.
        ->set($config_key, TRUE)
        ->save();

      // Visit the front page.
      $this->drupalGet('<front>');

      $config_key == 'site_name' ?
        $this->inBrandingBlock($this->whiteLabel->getName()) :
        $this->notInBrandingBlock($this->whiteLabel->getName());

      $config_key == 'site_slogan' ?
        $this->inBrandingBlock($this->whiteLabel->getSlogan()) :
        $this->notInBrandingBlock($this->whiteLabel->getSlogan());

      $config_key == 'site_logo' ?
        $this->inImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri()))) :
        $this->notInImagePath(file_url_transform_relative(file_create_url($this->whiteLabel->getLogo()->getFileUri())));
    }

    // Finally test the site name display option.
    $this->config('whitelabel.settings')
      // Disable all features.
      ->setData($this->configDefaults)
      // When testing site_name_display also enable site_name.
      ->set('site_name', TRUE)
      ->save();

    // Disable the site name display in the white label.
    $this->whiteLabel->setNameDisplay(FALSE)->save();

    foreach ([TRUE, FALSE] as $site_name_display_status) {
      $this->config('whitelabel.settings')
        ->set('site_name_display', $site_name_display_status)
        ->save();

      // Visit the front page.
      $this->drupalGet('<front>');

      // Site name display enabled follows white label value (FALSE), disabled
      // follows the system default (TRUE).
      $site_name_display_status ?
        $this->notInBrandingBlock($this->whiteLabel->getName()) :
        $this->inBrandingBlock($this->whiteLabel->getName());
    }
  }

}
