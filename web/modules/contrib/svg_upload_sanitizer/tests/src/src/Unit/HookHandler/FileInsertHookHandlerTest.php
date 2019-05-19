<?php

namespace Drupal\Tests\svg_upload_sanitizer\Unit\HookHandler;

use Drupal\svg_upload_sanitizer\Helper\FileHelper;
use Drupal\svg_upload_sanitizer\Helper\SanitizerHelper;
use Drupal\svg_upload_sanitizer\HookHandler\FileInsertHookHandler;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit test class for the FileInsertHookHandler class.
 *
 * @package Drupal\Tests\svg_upload_sanitizer\Unit\HookHandler
 */
class FileInsertHookHandlerTest extends UnitTestCase {

  public function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $container
      ->expects($this->atLeast(2))
      ->method('get')
      ->withConsecutive(
        ['svg_upload_sanitizer.helper.sanitizer'],
        ['svg_upload_sanitizer.helper.file']
      )
      ->willReturnOnConsecutiveCalls(
        $this->createMock(SanitizerHelper::class),
        $this->createMock(FileHelper::class)
      );

    $this->assertInstanceOf(FileInsertHookHandler::class, FileInsertHookHandler::create($container));
  }

}
