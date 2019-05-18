<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Media\ManagedFile;

use Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators\ImageExtensionUploadValidatorKit;
use Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators\ImageResolutionUploadValidatorKit;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Media\ManagedFile\ImageKit
 * @group kit
 */
class ImageKitTest extends KitTestBase {
  public function testDefaults() {
    $image = $this->k->image();
    $this->assertArrayEquals([
      'managed_image' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['png gif jpg jpeg'],
        ],
      ],
    ], [
      $image->getID() => $image->getArray(),
    ]);
  }

  public function testCustomID() {
    $image = $this->k->image('foo');
    $this->assertEquals('foo', $image->getID());
  }

  public function testTitle() {
    $image = $this->k->image()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'managed_image' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['png gif jpg jpeg'],
        ],
        '#title' => 'Foo',
      ],
    ], [
      $image->getID() => $image->getArray(),
    ]);
  }

  public function testDescription() {
    $image = $this->k->image()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'managed_image' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['png gif jpg jpeg'],
        ],
        '#description' => 'Foo',
      ],
    ], [
      $image->getID() => $image->getArray(),
    ]);
  }

  public function testValue() {
    $image = $this->k->image()
      ->setValue('foo');
    $this->assertArrayEquals([
      'managed_image' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['png gif jpg jpeg'],
        ],
        '#value' => 'foo',
      ],
    ], [
      $image->getID() => $image->getArray(),
    ]);
  }

  public function testMultiple() {
    $image = $this->k->image()
      ->setMultiple();
    $this->assertArrayEquals([
      'managed_image' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['png gif jpg jpeg'],
        ],
        '#multiple' => TRUE,
      ],
    ], [
      $image->getID() => $image->getArray(),
    ]);
  }

  public function testUploadLocation() {
    $image = $this->k->image()
      ->setUploadLocation('foo');
    $this->assertArrayEquals([
      'managed_image' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['png gif jpg jpeg'],
        ],
        '#upload_location' => 'foo',
      ],
    ], [
      $image->getID() => $image->getArray(),
    ]);
  }

  public function testUploadValidators() {
    $image = $this->k->image()
      ->setValidExtensions(['psd', 'tiff'])
      ->setMaxResolution(1024, 768);
    $this->assertArrayEquals([
      'managed_image' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'file_validate_extensions' => ['psd tiff'],
          'file_validate_image_resolution' => ['1024x768'],
        ],
      ],
    ], [
      $image->getID() => $image->getArray(),
    ]);
  }
}
