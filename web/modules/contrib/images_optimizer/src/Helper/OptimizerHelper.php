<?php

namespace Drupal\images_optimizer\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\images_optimizer\Form\ConfigurationForm;
use Drupal\images_optimizer\Optimizer\OptimizerInterface;
use Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector;
use Psr\Log\LoggerAwareTrait;

/**
 * Main helper class of our module.
 *
 * @package Drupal\images_optimizer\Helper
 */
class OptimizerHelper {

  use LoggerAwareTrait;

  /**
   * The optimizer service collector.
   *
   * @var \Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector
   */
  private $optimizerServiceCollector;

  /**
   * The Drupal file system helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Our module main configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $configuration;

  /**
   * OptimizerHelper constructor.
   *
   * @param \Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector $optimizer_service_collector
   *   The optimizer service collector.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The Drupal file system helper.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(OptimizerServiceCollector $optimizer_service_collector, FileSystemInterface $file_system, ConfigFactoryInterface $config_factory) {
    $this->optimizerServiceCollector = $optimizer_service_collector;
    $this->fileSystem = $file_system;

    $this->configuration = $config_factory->get(ConfigurationForm::MAIN_CONFIGURATION_NAME);
  }

  /**
   * Get the optimizers indexed by every supported mime type.
   *
   * @return \Drupal\images_optimizer\Optimizer\OptimizerInterface[]
   *   The optimizers, indexed by one supported mime type and their service ids.
   */
  public function getBySupportedMimeType() {
    $optimizers = [];

    foreach ($this->optimizerServiceCollector->all() as $serviceId => $optimizer) {
      foreach ($optimizer->getSupportedMimeTypes() as $mimeType) {
        if (!isset($optimizers[$mimeType])) {
          $optimizers[$mimeType] = [];
        }

        $optimizers[$mimeType][$serviceId] = $optimizer;
      }
    }

    return $optimizers;
  }

  /**
   * Try to optimize an image.
   *
   * @param string $mime_type
   *   The image mime type.
   * @param string $image_uri
   *   The image file URI.
   *
   * @return bool
   *   TRUE if the optimization was successful, FALSE otherwise.
   */
  public function optimize($mime_type, $image_uri) {
    $serviceId = $this->configuration->get($mime_type);
    if (!is_string($serviceId)) {
      return FALSE;
    }

    $optimizer = $this->optimizerServiceCollector->get($serviceId);
    if (!$optimizer instanceof OptimizerInterface) {
      return FALSE;
    }

    $image_path = $this->fileSystem->realpath($image_uri);
    if (FALSE === $image_path) {
      $this->logger->error(sprintf('Could not resolve the path of the image (URI: "%s").', $image_uri));

      return FALSE;
    }

    if (!$optimizer->optimize($image_path)) {
      $this->logger->error(sprintf('The optimization failed (optimizer: "%s", image path: "%s").', get_class($optimizer), $image_path));

      return FALSE;
    }

    return TRUE;
  }

}
