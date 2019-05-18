<?php

declare(strict_types = 1);

namespace Drupal\field_autovalue\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface for Field Autovalue plugins.
 */
interface FieldAutovalueInterface extends PluginInspectionInterface {

  /**
   * Sets the field value for a given field in an entity.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field list item to set the value on.
   */
  public function setAutovalue(FieldItemListInterface $field): void;

}
