<?php

declare(strict_types = 1);

namespace Drupal\field_autovalue\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Base class for Field Autovalue plugins.
 */
abstract class FieldAutovalueBase extends PluginBase implements FieldAutovalueInterface {

  /**
   * Returns the entity the field is being updated on.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field.
   * @param bool $original
   *   Whether to return the original or the current one in the change process.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  protected function getEntity(FieldItemListInterface $field, $original = FALSE):? ContentEntityInterface {
    $entity = $field->getParent()->getValue();
    if (!$original) {
      return $entity;
    }

    return $entity->original;
  }

}
