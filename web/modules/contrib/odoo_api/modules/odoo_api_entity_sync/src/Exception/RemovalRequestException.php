<?php

namespace Drupal\odoo_api_entity_sync\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Removal request exception.
 *
 * This exception may be thrown if the sync plugin requests Odoo object removal.
 */
class RemovalRequestException extends ExportException {

  /**
   * {@inheritdoc}
   */
  protected function getExceptionMessage() {
    $arguments = [
      '%entity_type' => $this->getEntityType(),
      '%odoo_model' => $this->getOdooModel(),
      '%export_type' => $this->getExportType(),
      '%id' => $this->getEntityId(),
    ];
    return (string) (new FormattableMarkup('Odoo object is pending removal. Entity type: %entity_type, Odoo model: %odoo_model, export type: %export_type, entity ID: %id.', $arguments));
  }

}
