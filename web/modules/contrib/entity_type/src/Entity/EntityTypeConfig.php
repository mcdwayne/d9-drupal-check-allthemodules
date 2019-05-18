<?php

namespace Drupal\entity_type\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the class for entity type config entities.
 *
 * @ConfigEntityType(
 *   id = "entity_type_config",
 *   label = @Translation("Entity type config"),
 *   admin_permission = "administer entity_type_config",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   config_prefix = "config",
 *   config_export = {
 *     "id",
 *   }
 * )
 */
class EntityTypeConfig extends ConfigEntityBase implements EntityTypeConfigInterface {

}
