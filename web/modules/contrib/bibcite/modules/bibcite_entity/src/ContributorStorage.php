<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Contributor storage.
 *
 * Allow to create Contributor entity using name property.
 */
class ContributorStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  protected function initFieldValues(ContentEntityInterface $entity, array $values = [], array $field_names = []) {
    $this->initContributorName($entity, $values);
    parent::initFieldValues($entity, $values, $field_names);
  }

  /**
   * Init contributor properties by full name string.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Contributor entity.
   * @param array $values
   *   Array of values.
   */
  protected function initContributorName(ContentEntityInterface $entity, array &$values = []) {
    if (isset($values['name'])) {
      $entity->set('name', $values['name']);

      foreach (['first_name', 'leading_title', 'middle_name', 'last_name', 'nick', 'prefix', 'suffix'] as $property) {
        if (!empty($value = $entity->{$property}->value)) {
          $values[$property] = $value;
        }
      }
    }
  }

}
