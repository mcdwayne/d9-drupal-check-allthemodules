<?php

namespace Drupal\Tests\fillpdf\Unit\FieldMapping;

use Drupal\fillpdf\FieldMapping\ImageFieldMapping;
use Drupal\Tests\UnitTestCase;

/**
 * @group fillpdf
 * @covers \Drupal\fillpdf\FieldMapping\ImageFieldMapping
 */
class ImageFieldMappingTest extends UnitTestCase {

  public function test__construct() {
    // Test valid and invalid instantiations.
    $image_field_mapping = new ImageFieldMapping('Dummy image', 'jpg');
    self::assertInstanceOf(ImageFieldMapping::class, $image_field_mapping);

    $this->setExpectedException(\InvalidArgumentException::class);
    new ImageFieldMapping('Dummy image', 'bmp');
  }

  public function testGetExtension() {
    $image_field_mapping = new ImageFieldMapping('Dummy image', 'jpg');
    self::assertEquals('jpg', $image_field_mapping->getExtension());
  }

}
