<?php

namespace Drupal\simple_amp\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines AMP Metadata annotation object.
 *
 * Plugin Namespace: Plugin\simple_amp\AmpMetadata
 *
 * @see \Drupal\simple_amp\Plugin\AmpMetadataManager
 * @see plugin_api
 *
 * @Annotation
 */
class AmpMetadata extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Entity type.
   *
   * @var array
   */
  public $entity_types;

  /**
   * Entity AMP metadata.
   *
   * @var array
   */
  public $metadata = [];

}
