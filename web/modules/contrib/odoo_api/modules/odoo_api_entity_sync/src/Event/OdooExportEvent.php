<?php

namespace Drupal\odoo_api_entity_sync\Event;

use Drupal\Core\Entity\EntityInterface;

/**
 * Odoo export event.
 */
class OdooExportEvent extends OdooEventBase {

  const CREATE = 'odoo_api_entity_sync.create';
  const WRITE = 'odoo_api_entity_sync.write';

  // Odoo object was removed *due to a plugin request*.
  // This event type shouldn't be dispatch for removal triggered by Drupal
  // entity delete.
  const DELETE_REQUEST = 'odoo_api_entity_sync.delete_request';

  /**
   * Exported entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Odoo object ID.
   *
   * @var int
   */
  protected $odooObjectId;

  /**
   * Event constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Exported entity object.
   * @param string $odoo_model
   *   Odoo object model name.
   * @param string $export_type
   *   Export type.
   * @param int $odoo_id
   *   Odoo object ID.
   */
  public function __construct(EntityInterface $entity, $odoo_model, $export_type, $odoo_id) {
    parent::__construct($odoo_model, $export_type);
    $this->entity = $entity;
    $this->odooObjectId = $odoo_id;
  }

  /**
   * Entity getter.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Exported entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Odoo object ID getter.
   *
   * @return int
   *   Odoo object ID.
   */
  public function getOdooObjectId() {
    return $this->odooObjectId;
  }

}
