<?php

namespace Drupal\Tests\mimeinfo\Unit\File\MimeType;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass;
use Drupal\Tests\Core\File\MimeTypeGuesserTest as BaseMimeTypeGuesserTest;
use Drupal\mimeinfo\File\MimeType\MimeTypeGuesser;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class MimeTypeGuesserTest.
 *
 * @coversDefaultClass \Drupal\Core\File\MimeType\MimeTypeGuesser
 *
 * @group File
 */
class MimeTypeGuesserTest extends BaseMimeTypeGuesserTest {

  /**
   * Tests that "isSupported" method can prevent adding guesser.
   *
   * @covers ::addGuesser
   */
  public function testIsSupportedMimeTypeGuessers() {
    $container = new ContainerBuilder();

    $stream_wrapper_manager = new Definition(StreamWrapperManager::class);
    $file_mime_type_guesser = new Definition(MimeTypeGuesser::class);
    $file_mime_type_guesser
      ->addArgument($stream_wrapper_manager)
      ->addTag('service_collector', [
        'tag' => 'mime_type_guesser',
        'call' => 'addGuesser',
      ]);

    $container->setDefinition('stream_wrapper_manager', $stream_wrapper_manager);
    $container->setDefinition('file.mime_type.guesser', $file_mime_type_guesser);

    foreach ([
      'supported' => SupportedMimeTypeGuesser::class,
      'unsupported' => UnsupportedMimeTypeGuesser::class,
    ] as $type => $class) {
      $definition = new Definition($class);
      $definition->addTag('mime_type_guesser');

      $container->setDefinition("file.mime_type.guesser.$type", $definition);
    }

    $handler_pass = new TaggedHandlersPass();
    $handler_pass->process($container);

    $guessers = $this->readAttribute($container->get('file.mime_type.guesser'), 'guessers');

    if ($this->assertCount(1, $guessers)) {
      $this->assertContainsOnlyInstancesOf(SupportedMimeTypeGuesser::class, $guessers[0]);
    }
  }

}
