<?php

namespace Drupal\nivo_slider\Tests;

/**
 * Test configuring slide options.
 */
class OptionTest extends NivoSliderTestBase {

  /**
   * Public static fucntion getInfo.
   */
  public static function getInfo() {
    return [
      'name' => 'Options',
      'description' => 'Test configuring slider options.',
      'group' => 'Nivo Slider',
    ];
  }

  /**
   * Add a new slide and ensure that it was created successfully.
   */
  public function testOptionTest() {
    $file = $this->getTestImage();

    // Create a new slide.
    $edit = [];
    $edit['files[upload]'] = drupal_realpath($file->uri);
    $this->drupalPost('admin/structure/nivo-slider', $edit, t('Save configuration'));

    // Load the slider options administration page.
    $this->drupalGet('admin/structure/nivo-slider/options');
    $this->assertResponse(200, t('The privileged user can access the slider options administration page.'));

    $themes = ['bar', 'dark', 'default', 'light'];

    // Test to ensure that the slider theme can be changed.
    foreach ($themes as $theme) {
      $edit = [];
      $edit['nivo_slider_theme'] = $theme;
      $this->drupalPost('admin/structure/nivo-slider/options', $edit, t('Save configuration'));

      // Check to ensure that the slider is displayed with the proper theme.
      $this->drupalGet('<front>');
      $elements = $this->xpath('//div[@class="slider-wrapper theme-' . $theme . '"]');
      $this->assertEqual(count($elements), 1, t('There is exactly one slider with the current theme on the front page.'));
    }
  }

}
