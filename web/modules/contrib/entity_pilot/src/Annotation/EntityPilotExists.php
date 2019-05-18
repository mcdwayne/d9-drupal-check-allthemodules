<?php

namespace Drupal\entity_pilot\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation for plugins to determine if an entity already exists.
 *
 * Plugin Namespace: Plugin\entity_pilot\Exists.
 *
 * For a working example, see
 * \Drupal\entity_pilot\Plugin\entity_pilot\Exists\ExistByUuid
 *
 * @see \Drupal\entity_pilot\ExistsPluginInterface
 * @see \Drupal\entity_pilot\ExistsPluginManager
 * @see \Drupal\entity_pilot\ExistsPluginManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class EntityPilotExists extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
