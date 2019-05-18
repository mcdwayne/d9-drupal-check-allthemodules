<?php

namespace Drupal\odoo_api_entity_sync\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Base Odoo Event from which other odoo event may be defined.
 */
abstract class OdooEventBase extends Event {

  /**
   * Odoo model name.
   *
   * @var string
   */
  protected $odooModel;

  /**
   * Export type.
   *
   * @var string
   */
  protected $exportType;

  /**
   * Event constructor.
   *
   * @param string $odoo_model
   *   Odoo object model name.
   * @param string $export_type
   *   Export type.
   */
  public function __construct($odoo_model, $export_type) {
    $this->odooModel = $odoo_model;
    $this->exportType = $export_type;
  }

  /**
   * Odoo model getter.
   *
   * @return string
   *   Odoo object model name
   */
  public function getOdooModel() {
    return $this->odooModel;
  }

  /**
   * Export type getter.
   *
   * @return string
   *   Export type getter.
   */
  public function getExportType() {
    return $this->exportType;
  }

}
