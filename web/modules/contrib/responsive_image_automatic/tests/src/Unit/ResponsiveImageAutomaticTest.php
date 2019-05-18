<?php

/**
 * @file
 * Contains \Drupal\Tests\responsive_image_automatic\Unit\ResponsiveImageAutomaticTest.
 */

namespace Drupal\Tests\responsive_image_automatic\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Test the responsive image automatic setup.
 *
 * @group responsive_image_automatic
 */
class ResponsiveImageAutomaticTest extends UnitTestCase {

  /**
   * Test the standard derivative creation.
   */
  public function testDerivativeCreation() {
    $image_style = $this->getMockImageStyleEntity(1200, 1200);
    $writes = 1;
    $image_style
      ->expects($this->exactly(3))
      ->method('writeDerivative')
      ->willReturnCallback(function ($original, $new) use (&$writes) {
        switch ($writes) {
          case 1:
            $this->assertEquals($new, 'public://styles/large/upload_5000x5000.jpg');
            break;
          case 2:
            $this->assertEquals($new, 'public://styles/large/upload_5000x5000_800.jpg');
            break;
          case 3:
            $this->assertEquals($new, 'public://styles/large/upload_5000x5000_400.jpg');
            break;
        }
        $writes++;
      });
    $image_style->createDerivative('public://upload.jpg', 'public://styles/large/upload_5000x5000.jpg');
  }

  /**
   * Don't create derivatives which are smaller or equal to original derivative.
   */
  public function testDerivativesNotCreatedBiggerThanOriginal() {
    $image_style = $this->getMockImageStyleEntity(2000, 2000);
    $image_style
      ->expects($this->exactly(3))
      ->method('writeDerivative')
      ->willReturnCallback(function ($original, $new) {
        $this->assertEquals($original, 'public://upload.jpg');
        // Ensure no images larger than 1200x1200 are created, even though the
        // style specifies 2000x2000.
        $this->assertNotEquals($new, 'public://upload_1200x1200_1600.jpg');
        $this->assertNotEquals($new, 'public://upload_1200x1200_1200.jpg');
      });
    $image_style->createDerivative('public://upload.jpg', 'public://upload_1200x1200.jpg');
  }

  /**
   * Test no derivatives are created of the style is under the size threshold.
   */
  public function testDerivativesCreatedIfUnderThreshold() {
    $image_style = $this->getMockImageStyleEntity(500, 500);

    $image_style
      ->expects($this->exactly(1))
      ->method('writeDerivative')
      ->willReturnCallback(function ($original, $new) {
        $this->assertEquals($original, 'public://upload.jpg');
        $this->assertEquals($new, 'public://upload_1200x1200.jpg');
      });
    $image_style->createDerivative('public://upload.jpg', 'public://upload_1200x1200.jpg');
  }

  /**
   * Test that it's business as usual if there is no resize effect.
   */
  public function testNoResizeEffect() {
    $image_style = $this->getMockImageStyleEntity(1000, 1000, []);
    $image_style
      ->expects($this->exactly(1))
      ->method('writeDerivative')
      ->willReturnCallback(function ($original, $new) {
        $this->assertEquals($original, 'public://upload.jpg');
        $this->assertEquals($new, 'public://upload_1200x1200.jpg');
      });
    $image_style->createDerivative('public://upload.jpg', 'public://upload_1200x1200.jpg');
  }

  /**
   * Test if the original write fails, no derivatives are attempted.
   */
  public function testFailedImageCreation() {
    $image_style = $this->getMockImageStyleEntity(1000, 1000, NULL, FALSE);
    $image_style->createDerivative('public://upload.jpg', 'public://upload_1200x1200.jpg');
    $image_style->expects($this->never())->method('getAutomaticDerivativeUris');
  }

  /**
   * Get the mock image style entity.
   */
  public function getMockImageStyleEntity($resize_width, $resize_height, $image_effects = NULL, $write_return_value = NULL) {

    $resize_effect_mock = $this->getMockBuilder('Drupal\image\Plugin\ImageEffect\ResizeImageEffect')
      ->disableOriginalConstructor()
      ->setMethods(['getConfiguration'])
      ->getMock();
    $resize_effect_mock
      ->expects($this->any())
      ->method('getConfiguration')
      ->willReturn([
        'data' => [
          'width' => $resize_width,
          'height' => $resize_height,
        ],
      ]);

    $plugin_factory_mock = $this->getMockBuilder('Drupal\image\ImageEffectPluginCollection')
      ->disableOriginalConstructor()
      ->setMethods(['getIterator'])
      ->getMock();
    $plugin_factory_mock
      ->expects($this->any())
      ->method('getIterator')
      ->willReturn($image_effects === NULL ? [
        NULL,
        $resize_effect_mock,
        NULL,
      ] : $image_effects);

    $file_system_mock = $this->getMockBuilder('Drupal\Core\File\FileSystem')
      ->disableOriginalConstructor()
      ->setMethods(['dirname'])
      ->getMock();
    $file_system_mock
      ->expects($this->any())
      ->method('dirname')
      ->willReturnCallback(function ($path) {
        return 'public://styles/large';
      });

    $image_style_mock = $this->getMockBuilder('Drupal\responsive_image_automatic\Entity\ImageStyle')
      ->disableOriginalConstructor()
      ->setMethods([
        'writeDerivative',
        'getFilesystem',
        'getDimensions',
        'getEffects',
      ])
      ->getMock();
    $image_style_mock
      ->expects($this->any())
      ->method('getEffects')
      ->willReturn($plugin_factory_mock);
    // Allow us to mock image dimensions by specifying a filename that contains
    // DIGITSxDIGITS for the dimension values and have these work in the test
    // environment.
    $image_style_mock
      ->expects($this->any())
      ->method('getDimensions')
      ->willReturnCallback(function ($image_uri) {
        if (preg_match('/(\d*)(x)(\d*)/', $image_uri, $matches)) {
          return [
            'width' => $matches[1],
            'height' => $matches[3],
          ];
        }
        return FALSE;
      });
    $image_style_mock
      ->expects($this->any())
      ->method('writeDerivative')
      ->willReturn($write_return_value === NULL ? TRUE : $write_return_value);
    $image_style_mock
      ->expects($this->any())
      ->method('getFilesystem')
      ->willReturn($file_system_mock);

    return $image_style_mock;
  }

}
