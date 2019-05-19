<?php

namespace Drupal\Tests\text2image\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Utility\Random;

/**
 * Test to ensure that Generator service operates correctly.
 *
 * @group text2image
 */
class Text2ImageServiceGeneratorTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['text2image'];

  /**
   * Tests the service.
   */
  public function testService() {
    $service = \Drupal::service('text2image.generator');
    $this->assertInstanceOf('\Drupal\text2image\Text2ImageGenerator', $service);
    $this->assertEqual($service->getImagePath(), 'public://text2image/', 'Service image path not equal to public://text2image/');
    $image_path = 'public://text2image/test/';
    $service->setImagePath($image_path);
    $this->assertEqual($service->getImagePath(), 'public://text2image/test/', 'Service image path not equal to public://text2image/test/');

    $this->assertTrue($service->getSetting('width') > 0, 'Service default width not greater than 0');
    $this->assertTrue($service->getSetting('height') > 0, 'Service default height not greater than 0');
    $this->assertTrue($service->getSetting('bg_color') !== '', 'Service default bg_color is empty');
    $this->assertTrue($service->getSetting('fg_color') !== '', 'Service default fg_color is empty');
    $this->assertTrue($service->getSetting('font_file') == '', 'Service default font_file not empty');
    $this->assertTrue($service->getSetting('font_size') > 0, 'Service default font_size not greater than 0');

    $font_file = drupal_get_path('module', 'text2image') . '/fonts/liberation-fonts-ttf-2.00.1/LiberationMono-Regular.ttf';

    $settings = [
      'font_file' => $font_file,
    ];
    $service->init($settings);
    $this->assertEqual($service->getSetting('font_file'), $font_file, 'Service set font_file not correct');
    $this->assertTrue($service->getSetting('width') > 0, 'Service default width not greater than 0');
    $this->assertTrue($service->getSetting('height') > 0, 'Service default height not greater than 0');
    $this->assertTrue($service->getSetting('bg_color') !== '', 'Service default bg_color is empty');
    $this->assertTrue($service->getSetting('fg_color') !== '', 'Service default fg_color is empty');
    $this->assertTrue($service->getSetting('font_size') > 0, 'Service default font_size not greater than 0');

    $settings = [
      'width' => 100,
      'height' => 100,
      'fg_color' => '#eeeeee',
      'bg_color' => '#111111',
      'font_file' => $font_file,
      'font_size' => 10,
    ];
    $service->init($settings);
    $this->assertEqual($service->getSetting('width'), $settings['width'], 'Service set width not greater than 0');
    $this->assertEqual($service->getSetting('height'), $settings['height'], 'Service set height not greater than 0');
    $this->assertEqual($service->getSetting('bg_color'), $settings['bg_color'], 'Service set bg_color is empty');
    $this->assertEqual($service->getSetting('fg_color'), $settings['fg_color'], 'Service set fg_color is empty');
    $this->assertEqual($service->getSetting('font_file'), $settings['font_file'], 'Service set font_file not empty');
    $this->assertEqual($service->getSetting('font_size'), $settings['font_size'], 'Service set font_size not greater than 0');

    $result = $service->createFilename('test_1');
    $this->assertEqual($image_path . 'test_1', $result, 'Service create filename expected ' . $image_path . 'test' . ' got ' . $result);
    $result = $service->createFilename("test\x001");
    $this->assertEqual($image_path . 'test_1', $result, 'Service create filename expected ' . $image_path . 'test' . ' got ' . $result);
    $result = $service->createFilename("test\x1F1");
    $this->assertEqual($image_path . 'test_1', $result, 'Service create filename expected ' . $image_path . 'test' . ' got ' . $result);

    $result = $service->hex2rgba('');
    $this->assertFalse($result, 'Service did not return FALSE for empty color string');
    $result = $service->hex2rgba(0);
    $this->assertFalse($result, 'Service did not return FALSE for zero');

    $result = $service->hex2rgba('000000');
    $this->assertTrue(is_array($result), 'Service did not return array for color string of 6 chars no hash');
    $this->assertEqual(count($result), 3, 'Service did not return array with 3 elements for color string of 6 chars no hash');
    $this->assertEqual($result[0], 0, 'Service did not correct red value for 000000');
    $this->assertEqual($result[1], 0, 'Service did not correct green value for 000000');
    $this->assertEqual($result[2], 0, 'Service did not correct blue value for 000000');

    $result = $service->hex2rgba('000');
    $this->assertTrue(is_array($result), 'Service did not return array for color string of 3 chars no hash');
    $this->assertEqual(count($result), 3, 'Service did not return array with 3 elements for color string of 3 chars no hash');
    $this->assertEqual($result[0], 0, 'Service did not correct red value for 000');
    $this->assertEqual($result[1], 0, 'Service did not correct green value for 000');
    $this->assertEqual($result[2], 0, 'Service did not correct blue value for 000');

    $result = $service->hex2rgba('#000000');
    $this->assertTrue(is_array($result), 'Service did not return array for color string of 6 chars with hash');
    $this->assertEqual(count($result), 3, 'Service did not return array with 3 elements for color string of 6 chars with hash');
    $this->assertEqual($result[0], 0, 'Service did not correct red value for #000000');
    $this->assertEqual($result[1], 0, 'Service did not correct green value for #000000');
    $this->assertEqual($result[2], 0, 'Service did not correct blue value for #000000');

    $result = $service->hex2rgba('#000');
    $this->assertTrue(is_array($result), 'Service did not return array for color string of 3 chars with hash');
    $this->assertEqual(count($result), 3, 'Service did not return array with 3 elements for color string of 3 chars with hash');
    $this->assertEqual($result[0], 0, 'Service did not correct red value for #000');
    $this->assertEqual($result[1], 0, 'Service did not correct green value for #000');
    $this->assertEqual($result[2], 0, 'Service did not correct blue value for #000');

    $result = $service->hex2rgba('#ff0000');
    $this->assertTrue(is_array($result), 'Service did not return array');
    $this->assertEqual(count($result), 3, 'Service did not return array with 3 elements');
    $this->assertEqual($result[0], 255, 'Service did not correct red value for #ff0000');
    $this->assertEqual($result[1], 0, 'Service did not correct green value for #ff0000');
    $this->assertEqual($result[2], 0, 'Service did not correct blue value for #ff0000');

    $result = $service->hex2rgba('#00ff00');
    $this->assertTrue(is_array($result), 'Service did not return array');
    $this->assertEqual(count($result), 3, 'Service did not return array with 3 elements');
    $this->assertEqual($result[0], 0, 'Service did not correct red value for #00ff00');
    $this->assertEqual($result[1], 255, 'Service did not correct green value for #00ff00');
    $this->assertEqual($result[2], 0, 'Service did not correct blue value for #00ff00');

    $result = $service->hex2rgba('#0000ff');
    $this->assertTrue(is_array($result), 'Service did not return array');
    $this->assertEqual(count($result), 3, 'Service did not return array with 3 elements');
    $this->assertEqual($result[0], 0, 'Service did not correct red value for #0000ff');
    $this->assertEqual($result[1], 0, 'Service did not correct green value for #0000ff');
    $this->assertEqual($result[2], 255, 'Service did not correct blue value for #0000ff');

    // Generate a new image.
    $random = new Random();
    $text = $random->name(8, TRUE);
    $uri = 'public://text2image/test/' . $text . '.png';
    $result = $service->getImage($text);
    $this->assertInstanceOf('Drupal\Core\Image\Image', $result);
    $this->assertEqual($result->uri, $uri, 'Expected ' . $uri . ' got ' . $result->uri);
    $this->assertTrue(file_exists($uri), 'File not found at ' . $uri);
    $this->assertEqual($result->title, $text, 'Expected title ' . $text . ' got ' . $result->title);
    $this->assertEqual($result->alt, $text, 'Expected alt ' . $text . ' got ' . $result->alt);
    $this->assertEqual($result->width, $settings['width'], 'Expected width ' . $settings['width'] . ' got ' . $result->width);
    $this->assertEqual($result->height, $settings['height'], 'Expected height ' . $settings['height'] . ' got ' . $result->height);
    $filetime1 = filectime(drupal_realpath($result->uri));

    // Get the previously generated image.
    $result = $service->getImage($text);
    $this->assertInstanceOf('Drupal\Core\Image\Image', $result);
    $this->assertEqual($result->uri, $uri, 'Expected ' . $uri . ' got ' . $result->uri);
    $this->assertTrue(file_exists($uri), 'File not found at ' . $uri);
    $this->assertEqual($result->title, $text, 'Expected title ' . $text . ' got ' . $result->title);
    $this->assertEqual($result->alt, $text, 'Expected alt ' . $text . ' got ' . $result->alt);
    $this->assertEqual($result->width, $settings['width'], 'Expected width ' . $settings['width'] . ' got ' . $result->width);
    $this->assertEqual($result->height, $settings['height'], 'Expected height ' . $settings['height'] . ' got ' . $result->height);
    $filetime2 = filectime(drupal_realpath($result->uri));
    $this->assertEqual($filetime2, $filetime1, 'Expected timestamp ' . $filetime1 . ' got ' . $filetime2);

    // Replace the previously generated image.
    // Wait a sec for timestamp difference check.
    sleep(1);
    $settings['width'] = 200;
    $settings['height'] = 200;
    $service->init($settings);
    $result = $service->getImage($text, TRUE);
    $this->assertInstanceOf('Drupal\Core\Image\Image', $result);
    $this->assertEqual($result->uri, $uri, 'Expected ' . $uri . ' got ' . $result->uri);
    $this->assertTrue(file_exists($uri), 'File not found at ' . $uri);
    $this->assertEqual($result->title, $text, 'Expected title ' . $text . ' got ' . $result->title);
    $this->assertEqual($result->alt, $text, 'Expected alt ' . $text . ' got ' . $result->alt);
    $this->assertEqual($result->width, $settings['width'], 'Expected width ' . $settings['width'] . ' got ' . $result->width);
    $this->assertEqual($result->height, $settings['height'], 'Expected height ' . $settings['height'] . ' got ' . $result->height);
    $filetime3 = filectime(drupal_realpath($result->uri));
    $this->assertNotEqual($filetime3, $filetime1, 'Expected new timestamp got ' . $filetime3);
  }

}
