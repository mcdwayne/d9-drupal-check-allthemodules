<?php

namespace Drupal\odoo_api_entity_sync\Exception;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Plugin logic exception.
 *
 * This exception may be thrown if inconsistency in plugin logic is detected,
 * typically indicating a bug.
 */
class PluginLogicException extends ExportException {

  /**
   * {@inheritdoc}
   */
  protected function getExceptionMessage() {
    $arguments = [
      '%message' => $this->getMessage(),
      '%entity_type' => $this->getEntityType(),
      '%odoo_model' => $this->getOdooModel(),
      '%id' => $this->getEntityId(),
    ];
    return (string) (new FormattableMarkup('Sync plugin logic error. Message: %message. Entity type: %entity_type, Odoo model: %odoo_model, entity ID: %id.', $arguments));
  }

}
