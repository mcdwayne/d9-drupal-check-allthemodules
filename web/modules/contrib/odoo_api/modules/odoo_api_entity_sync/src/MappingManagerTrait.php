<?php

namespace Drupal\odoo_api_entity_sync;

/**
 * Allows setter injection and simple usage of the service.
 */
trait MappingManagerTrait {

  /**
   * The Odoo mapping manager.
   *
   * @var \Drupal\odoo_api_entity_sync\MappingManagerInterface
   */
  protected $odooMappingManager;

  /**
   * Sets the Odoo mapping manager.
   *
   * @param \Drupal\odoo_api_entity_sync\MappingManagerInterface $odoo_mapping_manager
   *   The Odoo mapping manager.
   *
   * @return $this
   */
  public function setOdooMappingManager(MappingManagerInterface $odoo_mapping_manager) {
    $this->odooMappingManager = $odoo_mapping_manager;
    return $this;
  }

  /**
   * Gets the Odoo mapping manager.
   *
   * @return \Drupal\odoo_api_entity_sync\MappingManagerInterface
   *   The Odoo mapping manager.
   */
  public function getOdooMappingManager() {
    if (empty($this->odooMappingManager)) {
      /** @var \Drupal\odoo_api_entity_sync\MappingManagerInterface $odooMappingManager */
      $this->odooMappingManager = \Drupal::service('odoo_api_entity_sync.mapping');
    }
    return $this->odooMappingManager;
  }

}
