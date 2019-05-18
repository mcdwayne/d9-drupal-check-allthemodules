<?php
/**
 * @file
 * Tests for dummyimage module.
 */

namespace Drupal\dummyimage\Tests;
use Drupal\simpletest\WebTestBase;

/**
 * Tests dummyimage stuff.
 *
 * Make sure there are kittehs.
 *
 * @group dummyimage
 */
class DummyImageTest extends WebTestBase {

  public static $modules = array('dummyimage', 'image');

  /**
   * Test that dummyimage is altering urls according to the module settings.
   */
  public function testDummyUrlAlters() {
    // Create an image.
    $files = $this->drupalGetTestFiles('image');
    $file = reset($files);
    $original_uri = file_unmanaged_copy($file->uri, 'public://', FILE_EXISTS_RENAME);
    $width = rand(50, 400);
    $height = rand(80, 600);
    $altered_url = "http://dummyimage.com/{$width}x{$height}";

    $rendered = $this->renderImageFromUri($original_uri, $width, $height);
    $this->assertTrue(strpos($rendered, $altered_url), "Setting is 'all' - image url was altered");

    // Kitteh!
    \Drupal::config('dummyimage.settings')->set('dummyimages_service', 'placekitten');
    $altered_url = "http://placekitten.com/{$width}/{$height}";
    $rendered = $this->renderImageFromUri($original_uri, $width, $height);
    $this->assertTrue(strpos($rendered, $altered_url), "Setting is 'all' and new provider is used - image url was altered correctly");

    \Drupal::config('dummyimage.settings')->set('dummyimages_generate', 'none');
    $rendered = $this->renderImageFromUri($original_uri, $width, $height);
    $this->assertFalse(strpos($rendered, $altered_url), "Setting is 'none' - image url was not altered");

    \Drupal::config('dummyimage.settings')->set('dummyimages_generate', 'missing');
    $rendered = $this->renderImageFromUri($original_uri, $width, $height);
    $this->assertFalse(strpos($rendered, $altered_url), "Setting is 'missing' - image url was not altered because image exists");

    // Delete the file we are rendering.
    file_unmanaged_delete($original_uri);
    $rendered = $this->renderImageFromUri($original_uri, $width, $height);
    $this->assertTrue(strpos($rendered, $altered_url), "Setting is 'missing' - image url was altered because image does not exist");

  }

  /**
   * Helper function to render an image.
   *
   * @param string $uri
   *   The image uri.
   * @param integer $width
   *   Image width.
   * @param integer $height
   *   Image height.
   *
   * @return string
   *   The rendered image.
   */
  protected function renderImageFromUri($uri, $width, $height) {
    $element = array(
      '#theme' => 'image_style',
      '#style_name' => 'medium',
      '#uri' => $uri,
      '#width' => $width,
      '#height' => $height,
    );
    return drupal_render($element);
  }
}
