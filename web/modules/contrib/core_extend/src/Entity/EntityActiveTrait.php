<?php

namespace Drupal\core_extend\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Provides a trait for interacting with the status of an entity.
 */
trait EntityActiveTrait {

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    $key = $this->getEntityType()->getKey('status')?:'status';
    return $this->get($key)->value == 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isInactive() {
    $key = $this->getEntityType()->getKey('status')?:'status';
    return $this->get($key)->value == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function activate() {
    $key = $this->getEntityType()->getKey('status')?:'status';
    $this->get($key)->value = 1;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function inactivate() {
    $key = $this->getEntityType()->getKey('status')?:'status';
    $this->get($key)->value = 0;
    return $this;
  }

  /**
   * Returns an array of base field definitions for active field.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to add the active field to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   *   Thrown when the entity type does not implement EntityActiveInterface.
   */
  protected static function activeBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    if (!is_subclass_of($entity_type->getClass(), EntityActiveInterface::class)) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not implement \Drupal\core_extend\Entity\EntityActiveInterface.');
    }

    $key = $entity_type->getKey('status') ?: 'status';

    return [
      $key => BaseFieldDefinition::create('boolean')
        ->setLabel(t('Active status'))
        ->setDescription(t('A boolean indicating whether the @entity_label is activated.', ['@entity_label' => $entity_type->getLabel()]))
        ->setDefaultValue(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'boolean_checkbox',
          'settings' => [
            'display_label' => TRUE,
          ],
          'weight' => -5,
        ]),
    ];
  }

}
