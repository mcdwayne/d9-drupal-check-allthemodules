<?php

namespace Drupal\odoo_api_entity_sync\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Odoo entity sync plugin manager interface.
 */
interface EntitySyncPluginManagerInterface extends PluginManagerInterface {

  /**
   * Get Odoo sync plugin for given entity type and Odoo model.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   *
   * @return \Drupal\odoo_api_entity_sync\Plugin\EntitySyncInterface|false
   *   Entity sync plugin.
   *
   * @throws \Drupal\odoo_api_entity_sync\Plugin\Exception\MissingPluginException
   *   Missing sync plugin for given entity type and Odoo model.
   */
  public function getInstanceByType($entity_type, $odoo_model, $export_type = 'default');

}
