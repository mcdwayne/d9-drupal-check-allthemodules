<?php

namespace Drupal\odoo_api_entity_sync\Exception;

use Exception;
use Throwable;

/**
 * Generic export exception class.
 */
abstract class ExportException extends Exception {

  protected $entityType;
  protected $odooModel;
  protected $exportType;
  protected $entityId;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity_type, $odoo_model, $export_type, $entity_id, Throwable $previous = NULL, $message = '') {
    parent::__construct($message, 0, $previous);
    $this->entityType = $entity_type;
    $this->odooModel = $odoo_model;
    $this->exportType = $export_type;
    $this->entityId = $entity_id;
  }

  /**
   * Get entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get Odoo model.
   */
  public function getOdooModel() {
    return $this->odooModel;
  }

  /**
   * Get export type.
   */
  public function getExportType() {
    return $this->exportType;
  }

  /**
   * Get entity ID.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Get error message for logging in Drupal.
   *
   * @return string
   *   Error message.
   */
  public function getLogMessage() {
    $message = get_class($this) . ' thrown:' . PHP_EOL;
    $message .= $this->getExceptionMessage();

    if ($previous = $this->getPrevious()) {
      $message .= PHP_EOL . 'Previous exception:' . PHP_EOL;
      if ($previous instanceof self) {
        $message .= $previous->getLogMessage();
      }
      else {
        $message .= $previous->getMessage();
      }
    }

    return $message;
  }

  /**
   * Get human-readable error message.
   *
   * @return string
   *   Error message.
   */
  abstract protected function getExceptionMessage();

}
