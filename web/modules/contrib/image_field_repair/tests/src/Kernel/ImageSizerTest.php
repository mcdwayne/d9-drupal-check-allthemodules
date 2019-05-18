<?php

namespace Drupal\Tests\image_field_repair\Kernel;

use Drupal\image_field_repair\ImageSizer;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests get image size.
 *
 * @group image_field_repair
 */
class ImageSizerTest extends KernelTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'system', 'image_field_repair', 'simpletest'];

  /**
   * Tests get image size.
   */
  public function testImageSizer() {
    $imageSizer = new ImageSizer();
    $this->getTestFiles('image');
    $this->getTestFiles('text');
    $data = [
      'public://image-test.png' => [40, 20],
      'public://image-test-transparent-indexed.gif' => [20, 17],
      'public://image-test.gif' => [40, 20],
      'public://image-test-transparent-out-of-range.gif' => [40, 20],
      'public://image-test-no-transparency.gif' => [40, 20],
      'public://image-2.jpg' => [80, 60],
      'public://image-test.jpg' => [40, 20],
      'public://image-1.png' => [360, 240],
      'https://www.drupal.org/files/cta_multiple/featured_image/highlights_drupal.jpg' => [600, 410],
      '//www.drupal.org/files/cta_multiple/featured_image/highlights_drupal.jpg' => [600, 410],
      'public://text-0.txt' => FALSE,
      'public://not-found.jpg' => FALSE,
    ];

    foreach ($data as $uri => $expected_size) {
      $actual_size = $imageSizer->getDimensions($uri);
      if (is_array($expected_size)) {
        $this->assertSame($expected_size[0], $actual_size[0]);
        $this->assertSame($expected_size[1], $actual_size[1]);
      }
      else {
        $this->assertSame($expected_size, $actual_size);
      }
    }
  }

}
