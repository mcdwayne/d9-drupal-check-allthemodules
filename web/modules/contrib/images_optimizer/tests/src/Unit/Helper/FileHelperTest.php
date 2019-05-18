<?php

namespace Drupal\Tests\images_optimizer\Unit\Helper;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\images_optimizer\Helper\FileHelper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Unit test class for the FileHelper class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\Helper
 */
class FileHelperTest extends UnitTestCase {

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
   * @var \Drupal\images_optimizer\Helper\FileHelper
   */
  private $fileHelper;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->fileSystem = $this->createMock(FileSystemInterface::class);

    $this->logger = new Logger();

    $this->fileHelper = new FileHelper($this->fileSystem);
    $this->fileHelper->setLogger($this->logger);
  }

  /**
   * Test updateSize() when the file path could not be resolved.
   */
  public function testUpdateSizeWhenTheFilePathCouldNotBeResolved() {
    list($file) = $this->prepareUpdateSize(FALSE);

    $this->assertFalse($this->fileHelper->updateSize($file));

    $logs = $this->logger->getLogs('error');
    $this->assertCount(1, $logs);
    $this->assertSame('Could not resolve the path of the file (URI: "public://fileuri").', reset($logs));

  }

  /**
   * Test updateSize() when the file size could not be gotten.
   */
  public function testUpdateSizeWhenTheFileSizeCouldNotBeGotten() {
    list($file) = $this->prepareUpdateSize(TRUE, FALSE);

    $this->assertFalse($this->fileHelper->updateSize($file));

    $logs = $this->logger->getLogs('error');
    $this->assertCount(1, $logs);
    $this->assertSame('Could not get the file size (path: "something/that/will/never/exists.casper").', reset($logs));
  }

  /**
   * Test updateSize() when the file could not be saved.
   */
  public function testUpdateSizeWhenTheFileCouldNotBeSaved() {
    list ($file, $filePath) = $this->prepareUpdateSize(TRUE, TRUE, FALSE);

    $file
      ->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn(28);

    $this->assertTrue($this->fileHelper->updateSize($file));

    $logs = $this->logger->getLogs('error');
    $this->assertCount(1, $logs);
    $this->assertSame(sprintf('Could not save the file (fid: "28", path: "%s").', $filePath), reset($logs));
  }

  /**
   * Test updateSize().
   */
  public function testUpdateSize() {
    list($file) = $this->prepareUpdateSize(TRUE, TRUE, TRUE);

    $this->assertTrue($this->fileHelper->updateSize($file));
  }

  /**
   * Prepare the context for the updateSize() method tests.
   *
   * @param bool $pathIsResolved
   *   TRUE if the file path was resolved, FALSE otherwise.
   * @param bool $filePathExists
   *   TRUE if the file path exists, FALSE otherwise.
   * @param bool $fileSaveIsSuccessful
   *   TRUE if the file save was successful, FALSE otherwise.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   A mocked optimizer.
   */
  private function prepareUpdateSize($pathIsResolved, $filePathExists = FALSE, $fileSaveIsSuccessful = FALSE) {
    $filePath = $filePathExists ? sprintf('%s/fixtures/image.png', __DIR__) : 'something/that/will/never/exists.casper';

    $this->fileSystem
      ->expects($this->atLeastOnce())
      ->method('realpath')
      ->with('public://fileuri')
      ->willReturn($pathIsResolved ? $filePath : FALSE);

    $file = $this->createMock(FileInterface::class);
    $file
      ->expects($this->atLeastOnce())
      ->method('getFileUri')
      ->willReturn('public://fileuri');

    if ($filePathExists) {
      $file
        ->expects($this->atLeastOnce())
        ->method('setSize')
        ->with(filesize($filePath));
      $mocker = $file
        ->expects($this->atLeastOnce())
        ->method('save');
      if (!$fileSaveIsSuccessful) {
        $mocker->willThrowException(new EntityStorageException());
      }
    }

    return [$file, $filePath];
  }

}
