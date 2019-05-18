<?php

namespace Drupal\Tests\images_optimizer\Kernel\HookHandler;

use Drupal\file\Entity\File;
use Drupal\images_optimizer\Helper\FileHelper;
use Drupal\images_optimizer\Helper\OptimizerHelper;
use Drupal\images_optimizer\HookHandler\FileInsertHookHandler;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel test class for the FileInsertHookHandler class.
 *
 * @package Drupal\Tests\images_optimizer\Kernel\HookHandler
 */
class FileInsertHookHandlerTest extends KernelTestBase {

  /**
   * An image that exists.
   *
   * @var string
   */
  const VALID_IMAGE_FILE_URI = 'core/misc/druplicon.png';

  /**
   * The valid image mime type.
   *
   * @var string
   */
  const VALID_IMAGE_FILE_MIME_TYPE = 'image/png';

  /**
   * A file that exists but that is not an image.
   *
   * @var string
   */
  const EXISTING_FILE_THAT_IS_NOT_AN_IMAGE_URI = 'core/misc/drupal.js';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'file'];

  /**
   * The mocked optimizer helper.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $optimizerHelper;

  /**
   * The mocked file helper.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $fileHelper;

  /**
   * The file insert hook handler to test.
   *
   * @var \Drupal\images_optimizer\HookHandler\FileInsertHookHandler
   */
  private $fileInsertHookHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->optimizerHelper = $this->createMock(OptimizerHelper::class);

    $this->fileHelper = $this->createMock(FileHelper::class);

    $this->fileInsertHookHandler = new FileInsertHookHandler($this->optimizerHelper, $this->fileHelper);
  }

  /**
   * Test handle() when the file is not a valid image.
   *
   * @dataProvider processWhenTheFileIsNotValidImageProvider
   */
  public function testProcessWhenTheFileIsNotValidImage($fileUri) {
    $file = File::create();
    $file->setFileUri($fileUri);

    $this->assertFalse($this->fileInsertHookHandler->process($file));
  }

  /**
   * Provide the data for the testProcessWhenTheFileIsNotAValidImage() method.
   *
   * @return array
   *   The data.
   */
  public function processWhenTheFileIsNotValidImageProvider() {
    // The first one is for a file that has a valid image extension but that
    // does not exists.
    return [
      ['foo/bar/qux___.png'],
      [self::EXISTING_FILE_THAT_IS_NOT_AN_IMAGE_URI],
    ];
  }

  /**
   * Test process().
   *
   * @dataProvider processProvider
   */
  public function testProcess($expected, $optimizationIsSuccessful) {
    $this->optimizerHelper
      ->expects($this->atLeastOnce())
      ->method('optimize')
      ->with(self::VALID_IMAGE_FILE_MIME_TYPE, self::VALID_IMAGE_FILE_URI)
      ->willReturn($optimizationIsSuccessful);

    $file = File::create();
    $file->setFileUri(self::VALID_IMAGE_FILE_URI);
    $file->setMimeType(self::VALID_IMAGE_FILE_MIME_TYPE);

    if ($optimizationIsSuccessful) {
      $this->fileHelper
        ->expects($this->atLeastOnce())
        ->method('updateSize')
        ->with($file);
    }

    $this->assertSame($expected, $this->fileInsertHookHandler->process($file));
  }

  /**
   * Provide the data for the testProcess() method.
   *
   * @return array
   *   The data.
   */
  public function processProvider() {
    return [
      [FALSE, FALSE],
      [TRUE, TRUE],
    ];
  }

}
