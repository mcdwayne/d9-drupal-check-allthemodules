<?php

namespace Drupal\images_optimizer\Optimizer;

/**
 * Process optimizer that uses the jpegoptim binary.
 *
 * Check https://github.com/tjko/jpegoptim for more information.
 *
 * @package Drupal\images_optimizer\Optimizer
 */
final class JpegoptimOptimizer extends AbstractProcessOptimizer {

  /**
   * The service id.
   *
   * Should be the same in the module services file.
   *
   * @var string
   */
  const SERVICE_ID = 'images_optimizer.optimizer.jpegoptim';

  /**
   * {@inheritdoc}
   */
  public function getSupportedMimeTypes() {
    return ['image/jpeg'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'Jpegoptim';
  }

  /**
   * {@inheritdoc}
   */
  public function getCommandline($image_path) {
    return sprintf('%s --strip-all --max=%s --preserve --preserve-perms %s', $this->configuration->get('binary_path'), $this->configuration->get('quality'), $image_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationName() {
    return 'images_optimizer.jpegoptim.settings';
  }

}
