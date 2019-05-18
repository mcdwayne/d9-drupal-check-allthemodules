<?php

namespace Drupal\Tests\images_optimizer\Unit\HookHandler;

use Drupal\images_optimizer\Helper\FileHelper;
use Drupal\images_optimizer\Helper\OptimizerHelper;
use Drupal\images_optimizer\HookHandler\FileInsertHookHandler;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit test class for the FileInsertHookHandler class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\HookHandler
 */
class FileInsertHookHandlerTest extends UnitTestCase {

  /**
   * Test create().
   */
  public function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $container
      ->expects($this->atLeast(2))
      ->method('get')
      ->withConsecutive(
        ['images_optimizer.helper.optimizer'],
        ['images_optimizer.helper.file']
      )
      ->willReturnOnConsecutiveCalls(
        $this->createMock(OptimizerHelper::class),
        $this->createMock(FileHelper::class)
      );

    $this->assertInstanceOf(FileInsertHookHandler::class, FileInsertHookHandler::create($container));
  }

}
