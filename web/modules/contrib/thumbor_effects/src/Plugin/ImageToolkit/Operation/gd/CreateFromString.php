<?php

namespace Drupal\thumbor_effects\Plugin\ImageToolkit\Operation\gd;

use Drupal\system\Plugin\ImageToolkit\Operation\gd\GDImageToolkitOperationBase;

/**
 * Defines GD2 Create From String operation.
 *
 * @ImageToolkitOperation(
 *   id = "thumbor_effects_gd_create_from_string",
 *   toolkit = "gd",
 *   operation = "create_from_string",
 *   label = @Translation("Create from string"),
 *   description = @Translation("Create a new image from a string.")
 * )
 */
class CreateFromString extends GDImageToolkitOperationBase {

  /**
   * {@inheritdoc}
   */
  protected function arguments(): array {
    return [
      'string' => [
        'description' => 'The image data string.',
        'required' => TRUE,
        'default' => NULL,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(array $arguments): bool {
    if (empty($arguments['string'])) {
      $this->logger->notice("The image '@file' could not be created because the image data string is empty.", ['@file' => $this->getToolkit()->getSource()]);
      return FALSE;
    }

    // PHP installations using non-bundled GD do not have imagecreatefromstring.
    if (!\function_exists('imagecreatefromstring')) {
      $this->logger->notice("The image '@file' could not be created because the imagecreatefromstring() function is not available in this PHP installation.", ['@file' => $this->getToolkit()->getSource()]);
      return FALSE;
    }

    $resource = \imagecreatefromstring($arguments['string']);

    if (!$resource) {
      $this->logger->notice("The image '@file' could not be created, the source image may be corrupt or unsupported", ['@file' => $this->getToolkit()->getSource()]);
    }

    $this->getToolkit()->setResource($resource);

    return TRUE;
  }

}
