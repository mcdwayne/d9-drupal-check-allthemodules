<?php

namespace Drupal\visualn\Core;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for VisualN Data Generator plugins.
 */
interface DataGeneratorInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Generate data array
   *
   * @return array
   */
  public function generateData();

  /**
   * Generate resource object based on generated data.
   *
   * Usually generateData() is expected to generate plain array
   * though there may be cases when some other structure will be
   * return e.g. nested array. For that case a different resource
   * and thus a different raw resource format should be used.
   *
   * @see \Drupal\visualn\Annotation\VisualNDataGenerator
   *
   * @return \Drupal\visualn\Core\VisualNResourceInterface
   */
  public function generateResource();

}
