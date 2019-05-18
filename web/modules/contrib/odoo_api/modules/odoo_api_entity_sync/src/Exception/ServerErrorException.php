<?php

namespace Drupal\odoo_api_entity_sync\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Server error exception class.
 *
 * Used to wrap in XMLRPC errors.
 */
class ServerErrorException extends ExportException {

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
    return (string) (new FormattableMarkup('Odoo server error raised during the export. Entity type: %entity_type, Odoo model: %odoo_model, export type: %export_type, entity ID: %id.', $arguments));
  }

}
