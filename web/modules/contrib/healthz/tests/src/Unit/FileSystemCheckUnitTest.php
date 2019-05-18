<?php

namespace Drupal\Tests\healthz\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\healthz\Plugin\HealthzCheck\FileSystem;
use org\bovigo\vfs\vfsStream;

/**
 * Unit tests for the FileSystem plugin.
 *
 * TODO: This test is disabled as there seems to be an issue with vfsStream and
 * fopen throwing "failed to open stream" errors.
 *
 * @coversDefaultClass \Drupal\healthz\Plugin\HealthzCheck\FileSystem
 *
 * @group healthz
 */
abstract class FileSystemCheckUnitTest extends HealthzUnitTestBase {

  /**
   * The mock file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileSystem = $this->prophesize(FileSystemInterface::class);
    $this->plugin = new FileSystem([], 'test', [], $this->fileSystem->reveal());
  }

  /**
   * Tests the check function.
   */
  public function testCheck() {
    vfsStream::setup('temp');
    $this->fileSystem->realpath('temporary://')->willReturn(vfsStream::url('temp'));
    vfsStream::setup('public');
    $this->fileSystem->realpath('public://')->willReturn(vfsStream::url('public'));
    vfsStream::setup('private');
    $this->fileSystem->realpath('private://')->willReturn(vfsStream::url('private'));
    $this->assertTrue($this->plugin->check());
    $this->assertEmpty($this->plugin->getErrors());

    // Test when directory can't be found.
    $this->fileSystem->realpath('temporary://')->willReturn(FALSE);
    $this->assertFalse($this->plugin->check());
    $this->assertCount(1, $this->plugin->getErrors());

    // Test when we can't write.
    chmod(vfsStream::url('public'), 400);
    $this->assertFalse($this->plugin->check());
    $this->assertCount(2, $this->plugin->getErrors());
  }

}
