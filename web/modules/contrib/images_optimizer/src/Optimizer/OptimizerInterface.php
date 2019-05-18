<?php

namespace Drupal\images_optimizer\Optimizer;

/**
 * Interface for the optimizers.
 *
 * @package Drupal\images_optimizer\Optimizer
 */
interface OptimizerInterface {

  /**
   * Optimize the image.
   *
   * @param string $image_path
   *   The absolute image path.
   *
   * @return bool
   *   TRUE if the optimization was successful, FALSE otherwise.
   */
  public function optimize($image_path);

  /**
   * Get the mime types supported by this optimizer.
   *
   * @return string[]
   *   An array of mime types.
   */
  public function getSupportedMimeTypes();

  /**
   * Get the name of the optimizer.
   *
   * It will be displayed in the configuration form.
   *
   * @return string
   *   The name.
   */
  public function getName();

}
