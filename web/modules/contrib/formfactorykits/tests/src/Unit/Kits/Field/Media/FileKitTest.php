<?php

namespace Drupal\Tests\formfactorykits\Unit\Kits\Field\Media;

use Drupal\Tests\formfactorykits\Unit\KitTestBase;

/**
 * @coversDefaultClass \Drupal\formfactorykits\Kits\Field\Media\FileKit
 * @group kit
 */
class FileKitTest extends KitTestBase {
  public function testDefaults() {
    $file = $this->k->fileUnmanaged();
    $this->assertArrayEquals([
      'file' => [
        '#type' => 'file',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testCustomID() {
    $file = $this->k->fileUnmanaged('foo');
    $this->assertEquals('foo', $file->getID());
  }

  public function testTitle() {
    $file = $this->k->fileUnmanaged()
      ->setTitle('Foo');
    $this->assertArrayEquals([
      'file' => [
        '#type' => 'file',
        '#title' => 'Foo',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testDescription() {
    $file = $this->k->fileUnmanaged()
      ->setDescription('Foo');
    $this->assertArrayEquals([
      'file' => [
        '#type' => 'file',
        '#description' => 'Foo',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testValue() {
    $file = $this->k->fileUnmanaged()
      ->setValue('foo');
    $this->assertArrayEquals([
      'file' => [
        '#type' => 'file',
        '#value' => 'foo',
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testMultiple() {
    $file = $this->k->fileUnmanaged()
      ->setMultiple();
    $this->assertArrayEquals([
      'file' => [
        '#type' => 'file',
        '#multiple' => TRUE,
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }

  public function testSize() {
    $file = $this->k->fileUnmanaged()
      ->setSize(5);
    $this->assertArrayEquals([
      'file' => [
        '#type' => 'file',
        '#size' => 5,
      ],
    ], [
      $file->getID() => $file->getArray(),
    ]);
  }
}
