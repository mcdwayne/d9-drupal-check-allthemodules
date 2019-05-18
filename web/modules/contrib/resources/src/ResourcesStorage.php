<?php

namespace Drupal\resources;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\resources\Entity\ResourcesInterface;

/**
 * Defines the storage handler class for Resources entities.
 *
 * This extends the base storage class, adding required special handling for
 * Resources entities.
 *
 * @ingroup resources
 */
class ResourcesStorage extends SqlContentEntityStorage implements ResourcesStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ResourcesInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {resources_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {resources_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ResourcesInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {resources_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('resources_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
