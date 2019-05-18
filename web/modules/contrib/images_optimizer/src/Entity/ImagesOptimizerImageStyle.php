<?php

namespace Drupal\images_optimizer\Entity;

use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Custom image style to optimize every derivative images generated.
 *
 * This image style replaces the base one thanks to the entity_type_alter hook.
 *
 * @package Drupal\images_optimizer\Entity
 */
class ImagesOptimizerImageStyle extends ImageStyle {

  /**
   * {@inheritdoc}
   */
  public function createDerivative($original_uri, $derivative_uri) {
    if (!parent::createDerivative($original_uri, $derivative_uri)) {
      return FALSE;
    }

    try {
      // This is how Drupal guess mime types of uploaded files in the File
      // entity.
      $mime_type = \Drupal::service('file.mime_type.guesser')->guess($derivative_uri);
    }
    catch (AccessDeniedException $e) {
      // If there is any exception we skip the optimization.
      return TRUE;
    }
    catch (FileNotFoundException $e) {
      return TRUE;
    }

    // If the mime type could not be guessed we skip the optimization.
    if (is_string($mime_type)) {
      \Drupal::service('images_optimizer.helper.optimizer')->optimize($mime_type, $derivative_uri);
    }

    // Whether the optimization was successful or not, the image derivative was
    // generated so we always return TRUE.
    return TRUE;
  }

}
