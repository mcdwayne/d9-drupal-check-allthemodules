<?php

namespace Drupal\mimeinfo;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\mimeinfo\File\MimeType\MimeTypeGuesser;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;

/**
 * Class MimeInfoServiceProvider.
 */
class MimeInfoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('file.mime_type.guesser')
      ->setClass(MimeTypeGuesser::class);

    foreach ([
      'fileinfo' => FileinfoMimeTypeGuesser::class,
      'filebinary' => FileBinaryMimeTypeGuesser::class,
    ] as $guesser => $class) {
      $service_id = "file.mime_type.guesser.$guesser";

      if (!$container->has($service_id)) {
        $definition = new Definition($class);
        $definition->addTag('mime_type_guesser', ['priority', 1]);

        $container->setDefinition($service_id, $definition);
      }
    }
  }

}
