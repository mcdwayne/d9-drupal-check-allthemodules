<?php

namespace Drupal\entity_gallery;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the storage handler class for entity galleries.
 *
 * This extends the base storage class, adding required special handling for
 * entity gallery entities.
 */
class EntityGalleryStorage extends SqlContentEntityStorage implements EntityGalleryStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EntityGalleryInterface $entity_gallery) {
    return $this->database->query(
      'SELECT vid FROM {entity_gallery_revision} WHERE egid=:egid ORDER BY vid',
      array(':egid' => $entity_gallery->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {entity_gallery_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(EntityGalleryInterface $entity_gallery) {
    return $this->database->query('SELECT COUNT(*) FROM {entity_gallery_field_revision} WHERE egid = :egid AND default_langcode = 1', array(':egid' => $entity_gallery->id()))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update('entity_gallery')
      ->fields(array('type' => $new_type))
      ->condition('type', $old_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('entity_gallery_revision')
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
