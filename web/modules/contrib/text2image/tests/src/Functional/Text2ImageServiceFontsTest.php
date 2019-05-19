<?php

namespace Drupal\Tests\text2image\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test to ensure that Fonts service operates correctly.
 *
 * @group text2image
 */
class Text2ImageServiceFontsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['text2image'];

  /**
   * Tests the Fonts service.
   */
  public function testService() {
    $path = drupal_get_path('module', 'text2image') . '/tests/fixtures/';
    $expected = [
      $path . 'dummyfonts/truetype/dummyfont2/dummyfont2.ttf' => 'dummyfont2',
      $path . 'dummyfonts/truetype/dummyfont1.ttf' => 'dummyfont1',
      $path . 'dummyfonts/truetype/dummyfont3.ttf' => 'dummyfont3',
    ];

    $service = \Drupal::service('text2image.fonts');
    $this->assertInstanceOf('\Drupal\text2image\Text2ImageFonts', $service);
    $fonts = $service->getInstalledFonts($path, TRUE);
    $this->assertTrue(is_array($fonts), 'Service did not return fresh array: ' . print_r($expected, 1));
    $this->assertEqual(count($fonts), 3, 'Service did not return correct array count: ' . print_r($fonts, 1));
    foreach ($expected as $file => $font) {
      $this->assertTrue(isset($fonts[$file]), 'Array element not found: ' . $file);
      $this->assertEqual($fonts[$file], $font, 'Array value not correct: ' . $file . ' => ' . $font);
    }

    $fonts = $service->getInstalledFonts($path, FALSE);
    $this->assertTrue(is_array($fonts), 'Service did not return array from cache: ' . print_r($fonts, 1));
    $this->assertEqual(count($fonts), 3, 'Service did not return correct array count from cache: ' . print_r($fonts, 1));
    foreach ($expected as $file => $font) {
      $this->assertTrue(isset($fonts[$file]), 'Array element from cache not found: ' . $file);
      $this->assertEqual($fonts[$file], $font, 'Array value from cache not correct: ' . $file . ' => ' . $font);
    }
  }

}
