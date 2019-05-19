<?php

namespace Drupal\Tests\svg_upload_sanitizer\Unit\Helper;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\svg_upload_sanitizer\Helper\SanitizerHelper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Unit test class for the SanitizeHelper class.
 *
 * @package Drupal\Tests\svg_upload_sanitizer\Unit\Helper
 */
class SanitizerHelperTest extends UnitTestCase {

  /**
   * The mocked file system.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $fileSystem;

  /**
   * The logger.
   *
   * @var \Symfony\Component\HttpKernel\Tests\Logger
   */
  private $logger;

  /**
   * The file helper to test.
   *
   * @var \Drupal\svg_upload_sanitizer\Helper\SanitizerHelper
   */
  private $sanitizerHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileSystem = $this->createMock(FileSystemInterface::class);

    $this->logger = new Logger();
    $this->sanitizerHelper = new SanitizerHelper($this->fileSystem);
    $this->sanitizerHelper->setLogger($this->logger);
  }

  public function testSanitizeWhenMimeTypeIsNotSvg() {
    $file = $this->createMock(FileInterface::class);
    $file
      ->expects($this->atLeastOnce())
      ->method('getMimeType')
      ->willReturn('image/png');

    $this->assertFalse($this->sanitizerHelper->sanitize($file));
  }

  public function testSanitizeWhenRealpathIsNotResolved() {
    list($file) = $this->prepareFile(FALSE);

    $this->assertFalse($this->sanitizerHelper->sanitize($file));

    $logs = $this->logger->getLogs('notice');
    $this->assertCount(1, $logs);
    $this->assertSame('Could not resolve the path of the file (URI: "public://fileuri").', reset($logs));
  }

  public function testSanitizeWhenFileDoesNotExist() {
    list($file) = $this->prepareFile(TRUE);

    $this->assertFalse($this->sanitizerHelper->sanitize($file));

    $logs = $this->logger->getLogs('notice');
    $this->assertCount(1, $logs);
    $this->assertSame('The file does not exist (path: "something/that/will/never/exists.casper").', reset($logs));
  }

  public function testSanitize() {
    list($file) = $this->prepareFile(TRUE, TRUE);

    $this->assertTrue($this->sanitizerHelper->sanitize($file));
  }

  private function prepareFile($pathIsResolved, $filePathExists = FALSE) {
    $filePath = $filePathExists ? sprintf('%s/fixtures/image.svg', __DIR__) : 'something/that/will/never/exists.casper';

    $fileUri = 'public://fileuri';

    $file = $this->createMock(FileInterface::class);
    $file
      ->expects($this->atLeastOnce())
      ->method('getMimeType')
      ->willReturn('image/svg+xml');
    $file
      ->expects($this->atLeastOnce())
      ->method('getFileUri')
      ->willReturn($fileUri);

    $this->fileSystem
      ->expects($this->atLeastOnce())
      ->method('realpath')
      ->with($fileUri)
      ->willReturn($pathIsResolved ? $filePath : FALSE);

    return [$file, $filePath];
  }

}
