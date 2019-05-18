<?php

namespace Drupal\images_optimizer\Optimizer;

/**
 * Process optimizer that uses the pngquant binary.
 *
 * Check https://pngquant.org for more information.
 *
 * @package Drupal\images_optimizer\Optimizer
 */
final class PngquantOptimizer extends AbstractProcessOptimizer {

  /**
   * The service id.
   *
   * Should be the same in the module services file.
   *
   * @var string
   */
  const SERVICE_ID = 'images_optimizer.optimizer.pngquant';

  /**
   * {@inheritdoc}
   */
  public function getSupportedMimeTypes() {
    return ['image/png'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'Pngquant';
  }

  /**
   * {@inheritdoc}
   */
  public function getCommandline($image_path) {
    return sprintf('%s --strip --quality %s-%s --skip-if-larger --force --output %s %s', $this->configuration->get('binary_path'), $this->configuration->get('minimum_quality'), $this->configuration->get('maximum_quality'), $image_path, $image_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationName() {
    return 'images_optimizer.pngquant.settings';
  }

}
