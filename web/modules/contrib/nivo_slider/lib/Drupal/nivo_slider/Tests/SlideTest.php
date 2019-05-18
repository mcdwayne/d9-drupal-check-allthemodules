<?php

namespace Drupal\nivo_slider\Tests;

/**
 * Test creating, editing and deleting slides.
 */
class SlideTest extends NivoSliderTestBase {

  /**
   * Public static function getInfo.
   */
  public static function getInfo() {
    return [
      'name' => 'Slides',
      'description' => 'Test creating, editing and deleting slides.',
      'group' => 'Nivo Slider',
    ];
  }

  /**
   * Add a new slide and ensure that it was created successfully.
   */
  public function testSlideTest() {
    // Check to ensure that the slider is not displayed.
    $this->drupalGet('<front>');
    $this->assertNoRaw('//div[@id="slider"]', 'There is no slider on the front page.');

    // Load the slider slides administration page.
    $this->drupalGet('admin/structure/nivo-slider');
    $this->assertResponse(200, t('The privileged user can access the slider slides administration page.'));

    $file = $this->getTestImage();

    // Create five new slide.
    for ($i = 0; $i <= 5; $i++) {
      $edit = [];
      $edit['files[upload]'] = drupal_realpath($file->uri);
      $this->drupalPost('admin/structure/nivo-slider', $edit, t('Save configuration'));
      $this->assertText(t('The configuration options have been saved.'));
    }

    // Check to ensure that the slider is displayed.
    $this->drupalGet('<front>');
    $elements = $this->xpath('//div[@id="slider"]');
    $this->assertEqual(count($elements), 1, t('There is exactly one slider on the front page.'));

    // Delete the five existing slides.
    for ($i = 5; $i <= 0; $i--) {
      $edit = [];
      $edit["images[{$i}][delete]"] = TRUE;
      $this->drupalPost('admin/structure/nivo-slider', $edit, t('Save configuration'));
      $this->assertText(t('The configuration options have been saved.'));
    }
  }

}
