<?php

namespace Drupal\gtm_datalayer\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for GTM dataLayer Processors.
 *
 * @see \Drupal\gtm_datalayer\Annotation\DataLayerProcessor
 */
interface DataLayerProcessorInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Renders dataLayer tags.
   *
   * @return array
   *   The rendered dataLayer tags.
   */
  public function render();

}
