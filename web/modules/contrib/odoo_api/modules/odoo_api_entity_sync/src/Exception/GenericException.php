<?php

namespace Drupal\odoo_api_entity_sync\Exception;

use Drupal\Component\Render\FormattableMarkup;
use Throwable;

/**
 * Generic exception class.
 *
 * May be used to log custom errors.
 */
class GenericException extends ExportException {

  /**
   * {@inheritdoc}
   */
  public function __construct($entity_type, $odoo_model, $export_type, $entity_id, $message, Throwable $previous = NULL) {
    parent::__construct($entity_type, $odoo_model, $export_type, $entity_id, $previous, $message);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExceptionMessage() {
    $arguments = [
      '%message' => $this->getMessage(),
      '%entity_type' => $this->getEntityType(),
      '%odoo_model' => $this->getOdooModel(),
      '%export_type' => $this->getExportType(),
      '%id' => $this->getEntityId(),
    ];
    return (string) (new FormattableMarkup('Generic export error. Message: %message. Entity type: %entity_type, Odoo model: %odoo_model, export type: %export_type, entity ID: %id.', $arguments));
  }

}
