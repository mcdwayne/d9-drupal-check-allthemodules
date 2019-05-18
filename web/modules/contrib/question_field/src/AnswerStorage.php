<?php

namespace Drupal\question_field;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Class AnswerStorage.
 */
class AnswerStorage {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * AnswerStorage constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $database, AccountInterface $current_user, TimeInterface $time) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->time = $time;
  }

  /**
   * Return the user's responses to the question field item.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item list.
   *
   * @return array
   *   The user's responses.
   */
  public function getItemValues(FieldItemListInterface $items) {
    $entity = $items->getEntity();
    $field_name = $items->getFieldDefinition()->getName();
    return $this->getEntityValues($entity, $field_name);
  }

  /**
   * Return the user's responses to the question on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the question field.
   * @param string $field_name
   *   The question field.
   *
   * @return array
   *   The user's responses.
   */
  public function getEntityValues(EntityInterface $entity, $field_name) {
    $value = $this->database->select('question_field', 'qf')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('field_name', $field_name)
      ->condition('uid', $this->currentUser->id())
      ->fields('qf', ['value'])
      ->execute()
      ->fetchField();
    return $value ? unserialize($value) : [];
  }

  /**
   * Save the user's responses to the question field item.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item list.
   * @param array $values
   *   The new values.
   */
  public function setItemValues(FieldItemListInterface $items, array $values) {
    $entity = $items->getEntity();
    $field_name = $items->getFieldDefinition()->getName();
    $this->setEntityValues($entity, $field_name, $values);
  }

  /**
   * Save the user's responses to the question field on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the question field.
   * @param string $field_name
   *   The question field.
   * @param array $values
   *   The new values.
   */
  public function setEntityValues(EntityInterface $entity, $field_name, array $values) {
    // Update the values.
    // @todo: patch D8 to accept upsert() array keys.
    $this->database->upsert('question_field')
      ->key('todo_patch_d8_to_accept_arrays_or_not_require_the_key')
      ->fields([
        'entity_type',
        'entity_id',
        'field_name',
        'uid',
        'value',
        'timestamp',
      ])
      ->values([
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'field_name' => $field_name,
        'uid' => $this->currentUser->id(),
        'value' => serialize($values),
        'timestamp' => $this->time->getRequestTime(),
      ])
      ->execute();

    // Invalidate the cache tag.
    $this->invalidateEntityCacheTags($entity, $field_name);
  }

  /**
   * Delete the user's responses to the question field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field item list.
   */
  public function deleteItemValues(FieldItemListInterface $items) {
    $entity = $items->getEntity();
    $field_name = $items->getFieldDefinition()->getName();
    $this->deleteEntityValues($entity, $field_name);
  }

  /**
   * Delete the user's responses to the question field on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the question field.
   * @param string $field_name
   *   The question field.
   */
  public function deleteEntityValues(EntityInterface $entity, $field_name) {
    // Delete the values.
    $this->database->delete('question_field')
      ->condition('entity_type', $entity->getEntityTypeId())
      ->condition('entity_id', $entity->id())
      ->condition('field_name', $field_name)
      ->condition('uid', $this->currentUser->id())
      ->execute();

    // Invalidate the cache tag.
    $this->invalidateEntityCacheTags($entity, $field_name);
  }

  /**
   * Return the cache tag for the question field.
   *
   * @return string
   *   The cache tag.
   */
  public function getItemCacheTags(FieldItemListInterface $items) {
    $entity = $items->getEntity();
    $field_name = $items->getFieldDefinition()->getName();
    return $this->getEntityCacheTags($entity, $field_name);
  }

  /**
   * Return the cache tag for the question field on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the question field.
   * @param string $field_name
   *   The question field.
   *
   * @return string
   *   The cache tag.
   */
  public function getEntityCacheTags(EntityInterface $entity, $field_name) {
    return implode(':', [
      'question_field',
      $entity->getEntityTypeId(),
      $entity->id(),
      $field_name,
      $this->currentUser->id(),
    ]);
  }

  /**
   * Invalidate the cache tags for the question field on the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the question field.
   * @param string $field_name
   *   The question field.
   */
  protected function invalidateEntityCacheTags(EntityInterface $entity, $field_name) {
    Cache::invalidateTags([$this->getEntityCacheTags($entity, $field_name)]);
  }

}
