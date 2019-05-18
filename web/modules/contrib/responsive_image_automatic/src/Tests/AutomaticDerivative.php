<?php
/**
 * @file
 * Test that image style quality works.
 */
namespace Drupal\responsive_image_automatic\Tests;

use Drupal\responsive_image_automatic\Entity\ImageStyle;
use Drupal\simpletest\WebTestBase;

/**
 * Test automatic derivative creation.
 *
 * @group responsive_image_automatic
 */
class AutomaticDerivative extends WebTestBase {

  public static $modules = ['responsive_image_automatic'];

  /**
   * Test that the image effect manipulates filesizes.
   */
  public function testAutomaticDerivative() {

    $test_image = drupal_get_path('module', 'responsive_image_automatic') . '/src/Tests/test.png';
    $derivative_uri = $this->publicFilesDirectory . '/test.jpg';

    $effect_uuid = 'addf0d06-42f9-4c75-a700-a33cafa25ea0';
    ImageStyle::create([
      'name' => 'resize_image',
      'label' => 'Resize Image',
      'status' => TRUE,
      'effects' => [
        $effect_uuid => [
          'id' => 'image_scale',
          'data' => [
            'width' => '1920',
            'height' => '1500',
            'upscale' => FALSE,
          ],
          'uuid' => $effect_uuid,
          'weight' => 0,
        ],
      ],
    ])->save();

    $style = ImageStyle::load('resize_image');
    $style->createDerivative($test_image, $derivative_uri);

    list($width, $height) = getimagesize($derivative_uri);
    $this->assertEqual($width, 1920);
    $this->assertEqual($height, 1234);

    list($width, $height) = getimagesize($this->publicFilesDirectory . '/test_1520.jpg');
    $this->assertEqual($width, 1520);
    $this->assertEqual($height, 977);

    list($width, $height) = getimagesize($this->publicFilesDirectory . '/test_1120.jpg');
    $this->assertEqual($width, 1120);
    $this->assertEqual($height, 720);

    list($width, $height) = getimagesize($this->publicFilesDirectory . '/test_720.jpg');
    $this->assertEqual($width, 720);
    $this->assertEqual($height, 463);

    list($width, $height) = getimagesize($this->publicFilesDirectory . '/test_320.jpg');
    $this->assertEqual($width, 320);
    $this->assertEqual($height, 206);
  }
}
