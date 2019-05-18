<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Media\ManagedFile;

use Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators\ImageExtensionUploadValidatorKit;
use Drupal\formfactorykits\Kits\Field\Media\ManagedFile\UploadValidators\ImageResolutionUploadValidatorKit;
use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Media\ManagedFile\FileKit
 * @group kit
 */
class FileKitTest extends KitTestBase {
  public function testDefaults() {
    $file = $this->k->file();
    $this->assertArrayEquals([
      'managed_file' => [
        '#type' => 'managed_file',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testCustomID() {
    $file = $this->k->file('foo');
    $this->assertEquals('foo', $file->getID());
  }

  public function testTitle() {
    $file = $this->k->file()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'managed_file' => [
        '#type' => 'managed_file',
        '#title' => 'Foo',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testDescription() {
    $file = $this->k->file()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'managed_file' => [
        '#type' => 'managed_file',
        '#description' => 'Foo',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testValue() {
    $file = $this->k->file()
      ->setValue('foo');
    $this->assertArrayEquals([
      'managed_file' => [
        '#type' => 'managed_file',
        '#value' => 'foo',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testMultiple() {
    $file = $this->k->file()
      ->setMultiple();
    $this->assertArrayEquals([
      'managed_file' => [
        '#type' => 'managed_file',
        '#multiple' => TRUE,
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testUploadLocation() {
    $file = $this->k->file()
      ->setUploadLocation('foo');
    $this->assertArrayEquals([
      'managed_file' => [
        '#type' => 'managed_file',
        '#upload_location' => 'foo',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testUploadValidators() {
    $file = $this->k->file()
      ->setUploadValidators([
        'foo' => ['a', 'b'],
        'bar' => ['c', 'd'],
      ])
      ->setUploadValidator(ImageExtensionUploadValidatorKit::create($this->k));
    $this->assertArrayEquals([
      'managed_file' => [
        '#type' => 'managed_file',
        '#upload_validators' => [
          'foo' => ['a', 'b'],
          'bar' => ['c', 'd'],
          'file_validate_extensions' => ['png gif jpg jpeg'],
        ],
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }
}
