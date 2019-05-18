<?php

namespace Drupal\core_extend\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Provides a trait for entities that have an owner.
 *
 * @see \Drupal\user\EntityOwnerInterface
 */
trait EntityOwnerTrait {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\user\UserInterface
   *   The owner User entity.
   */
  public function getOwner() {
    $key = $this->getEntityType()->getKey('uid')?:'user_id';
    return $this->get($key)->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get($this->getEntityType()->getKey('uid') ?: 'user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set($this->getEntityType()->getKey('uid') ?: 'user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set($this->getEntityType()->getKey('uid') ?: 'user_id', $account->id());
    return $this;
  }

  /**
   * Returns an array of base field definitions for owner.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to add the owner field to.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   An array of base field definitions.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   *   Thrown when the entity type does not implement EntityOwnerInterface
   *   or if it does not have a "uid" entity key.
   */
  protected static function ownerBaseFieldDefinitions(EntityTypeInterface $entity_type) {
    if (!is_subclass_of($entity_type->getClass(), EntityOwnerInterface::class)) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not implement \Drupal\core_extend\Entity\EntityOwnerInterface.');
    }
    if (!$entity_type->hasKey('uid')) {
      throw new UnsupportedEntityTypeDefinitionException('The entity type ' . $entity_type->id() . ' does not have a "published" entity key.');
    }

    return [
      $entity_type->getKey('uid') => BaseFieldDefinition::create('entity_reference')
        ->setLabel(t('Owned by'))
        ->setDescription(t('The owner user ID of the @entity_label.', ['@entity_label' => $entity_type->getLabel()]))
        ->setDefaultValueCallback(static::class . '::getCurrentUserId')
        ->setSetting('target_type', 'user')
        ->setSetting('handler', 'default')
        ->setDisplayOptions('form', [
          'type' => 'entity_reference_autocomplete',
          'weight' => 5,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => '60',
            'autocomplete_type' => 'tags',
            'placeholder' => '',
          ],
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE),
    ];
  }

  /**
   * Default value callback for 'user_id' field definitions.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
