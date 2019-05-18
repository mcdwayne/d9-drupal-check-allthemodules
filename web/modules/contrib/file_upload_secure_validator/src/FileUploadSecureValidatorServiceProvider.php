<?php

namespace Drupal\file_upload_secure_validator;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser as SymfonyFileinfoMimeTypeGuesser;

/**
 * Dynamically register a fileinfo validator service.
 *
 * Registers only if the fileinfo extension is available.
 */
class FileUploadSecureValidatorServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    if (SymfonyFileinfoMimeTypeGuesser::isSupported()) {
      $container->register(
        'file_upload_secure_validator',
        'Drupal\file_upload_secure_validator\Service\FileUploadSecureValidator'
      );
    }
  }

}
