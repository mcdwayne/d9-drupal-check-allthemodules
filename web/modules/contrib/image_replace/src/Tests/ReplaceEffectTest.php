<?php
/**
 * @file
 * Contains Drupal\image_replace\Tests\ReplaceEffectTest.
 */

namespace Drupal\image_replace\Tests;

use Drupal\image\Entity\ImageStyle;

/**
 * Tests functionality of the replace image effect.
 *
 * @group image_replace
 */
class ReplaceEffectTest extends ImageReplaceTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image_replace');

  /**
   * Tests functionality of the replace image effect.
   *
   * Functionality covered by this test include:
   * - image_replace_add()
   * - image_replace_get()
   * - image_replace_remove()
   * - image_replace_effect()
   */
  public function testReplaceEffect() {
    list($original_file, $replacement_file) = $this->createTestFiles();

    // Create an image style containing the replace effect.
    $style_name = 'image_replace_test';
    $style = $this->createImageStyle($style_name);

    // Apply the image style to a test image.
    $generated_url = ImageStyle::load($style_name)->buildUrl($original_file->getFileUri());
    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertResponse(200);

    // Assert that the result is the original image.
    $generated_uri = file_unmanaged_save_data($generated_image_data);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file if there is no replacement mapping.');

    // Set up a replacement image.
    image_replace_add($style_name, $original_file->getFileUri(), $replacement_file->getFileUri());
    ImageStyle::load($style_name)->flush();

    // Apply the image style to the test imge.
    $generated_url = ImageStyle::load($style_name)->buildUrl($original_file->getFileUri());
    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertResponse(200);

    // Assert that the result is the replacement image.
    $generated_uri = file_unmanaged_save_data($generated_image_data);
    $this->assertTrue($this->imageIsReplacement($generated_uri), 'The generated file should be the same as the replacement file.');

    // Set up a replacement image.
    image_replace_remove($style_name, $original_file->getFileUri(), $replacement_file->getFileUri());
    ImageStyle::load($style_name)->flush();

    // Apply the image style to a test image.
    $generated_url = ImageStyle::load($style_name)->buildUrl($original_file->getFileUri());
    $generated_image_data = $this->drupalGet($generated_url);
    $this->assertResponse(200);

    // Assert that the result is the original image.
    $generated_uri = file_unmanaged_save_data($generated_image_data);
    $this->assertTrue($this->imageIsOriginal($generated_uri), 'The generated file should be the same as the original file if the replacement mapping was removed.');
  }

}
