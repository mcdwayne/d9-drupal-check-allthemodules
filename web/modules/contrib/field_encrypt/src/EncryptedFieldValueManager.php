<?php

namespace Drupal\field_encrypt;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field_encrypt\Entity\EncryptedFieldValue;
use Drupal\field_encrypt\Entity\EncryptedFieldValueInterface;

/**
 * Manager containing common functions to manage EncryptedFieldValue entities.
 */
class EncryptedFieldValueManager implements EncryptedFieldValueManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Construct the CommentManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, QueryFactory $entity_query) {
    $this->entityManager = $entity_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public function createEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property, $encrypted_value) {
    $langcode = $entity->language()->getId();
    if ($encrypted_field_value = $this->getExistingEntity($entity, $field_name, $delta, $property)) {
      $encrypted_field_value->setEncryptedValue($encrypted_value);
      $encrypted_field_value->save();
    }
    else {
      // Create a new EncryptedFieldValue entity. The parent entity's (revision)
      // id might not be known yet, so the EncryptedFieldValue will be saved
      // by saveEncryptedFieldValues() later on.
      $encrypted_field_value = $this->entityManager->getStorage('encrypted_field_value')->create([
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => !$entity->isNew() ? $entity->id() : NULL,
        'entity_revision_id' => $this->getEntityRevisionId($entity),
        'field_name' => $field_name,
        'field_property' => $property,
        'field_delta' => $delta,
        'encrypted_value' => $encrypted_value,
        'langcode' => $langcode,
      ]);
      $entity->encrypted_field_values[] = $encrypted_field_value;
    }
    return $encrypted_field_value;
  }

  /**
   * {@inheritdoc}
   */
  public function saveEncryptedFieldValues(ContentEntityInterface $entity) {
    if (!empty($entity->encrypted_field_values)) {
      foreach ($entity->encrypted_field_values as $encrypted_field_value) {
        if ($encrypted_field_value instanceof EncryptedFieldValueInterface) {
          // Update the parent entity (revision) id, now that it's known.
          $encrypted_field_value->set('entity_id', $entity->id());
          $encrypted_field_value->set('entity_revision_id', $this->getEntityRevisionId($entity));
          // Actually save the EncryptedFieldValue entity.
          $encrypted_field_value->save();
        }
      }
      unset($entity->encrypted_field_values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEncryptedFieldValue(ContentEntityInterface $entity, $field_name, $delta, $property) {
    $field_value_entity = $this->getExistingEntity($entity, $field_name, $delta, $property, $entity->getRevisionId());
    if ($field_value_entity) {
      $langcode = $entity->language()->getId();
      if ($field_value_entity->hasTranslation($langcode)) {
        $field_value_entity = $field_value_entity->getTranslation($langcode);
      }
      return $field_value_entity->getEncryptedValue();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingEntity(ContentEntityInterface $entity, $field_name, $delta, $property) {
    $query = $this->entityQuery->get('encrypted_field_value')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('entity_revision_id', $this->getEntityRevisionId($entity))
      ->condition('langcode', $entity->language()->getId())
      ->condition('field_name', $field_name)
      ->condition('field_delta', $delta)
      ->condition('field_property', $property);
    $values = $query->execute();

    if (!empty($values)) {
      $id = array_shift($values);
      return EncryptedFieldValue::load($id);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntityEncryptedFieldValues(ContentEntityInterface $entity) {
    $field_values = $this->entityManager->getStorage('encrypted_field_value')->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);
    if ($field_values) {
      $this->entityManager->getStorage('encrypted_field_value')->delete($field_values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntityEncryptedFieldValuesForField(ContentEntityInterface $entity, $field_name) {
    $field_values = $this->entityManager->getStorage('encrypted_field_value')->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'field_name' => $field_name,
      'entity_revision_id' => $this->getEntityRevisionId($entity),
    ]);
    if ($field_values) {
      $this->entityManager->getStorage('encrypted_field_value')->delete($field_values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEncryptedFieldValuesForField($entity_type, $field_name) {
    $field_values = $this->entityManager->getStorage('encrypted_field_value')->loadByProperties([
      'entity_type' => $entity_type,
      'field_name' => $field_name,
    ]);
    if ($field_values) {
      $this->entityManager->getStorage('encrypted_field_value')->delete($field_values);
    }
  }

  /**
   * Get the revision ID to store for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return int
   *   The revision ID.
   */
  protected function getEntityRevisionId(ContentEntityInterface $entity) {
    if ($entity->isNew()) {
      return NULL;
    }

    return $entity->getEntityType()->hasKey('revision') ? $entity->getRevisionId() : $entity->id();
  }

}
