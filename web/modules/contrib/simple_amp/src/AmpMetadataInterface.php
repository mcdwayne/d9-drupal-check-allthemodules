<?php

namespace Drupal\simple_amp;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface AmpMetadata plugins.
 */
interface AmpMetadataInterface extends PluginInspectionInterface {

  /**
   * Return list of entity types.
   *
   * @return array
   */
  public function getEntityTypes($entity);

  /**
   * Return entity AMP metadata.
   *
   * @return array
   */
  public function getMetadata($entity);

}
