<?php

namespace Drupal\core_extend\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides a trait for tracking entity creation time.
 */
trait EntityCreatedTrait {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    $key = $this->getEntityType()->getKey('created') ?: 'created';
    return $this->get($key)->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $key = $this->getEntityType()->getKey('created') ?: 'created';
    $this->set($key, $timestamp);
    return $this;
  }

  /**
   * Returns an array of base field definitions for created timestamp.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to add the created field to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   *   Thrown when the entity type does not implement EntityCreatedInterface.
   */
  protected static function createdBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    if (!is_subclass_of($entity_type->getClass(), EntityCreatedInterface::class)) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not implement \Drupal\core_extend\Entity\EntityCreatedInterface.');
    }

    $key = $entity_type->getKey('created') ?: 'created';

    return [
      $key => BaseFieldDefinition::create('created')
        ->setLabel(t('Created'))
        ->setDescription(t('The time that the @entity_label was created.', ['@entity_label' => $entity_type->getLabel()])),
    ];
  }

}
