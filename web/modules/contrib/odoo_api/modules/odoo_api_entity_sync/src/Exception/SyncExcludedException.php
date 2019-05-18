<?php

namespace Drupal\odoo_api_entity_sync\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Sync exclusion exception.
 *
 * This exception may be thrown if given entity shouldn't be synced for some
 * reason.
 */
class SyncExcludedException extends ExportException {

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
    return (string) (new FormattableMarkup('Entity is excluded from sync. Entity type: %entity_type, Odoo model: %odoo_model, export type: %export_type, entity ID: %id.', $arguments));
  }

}
