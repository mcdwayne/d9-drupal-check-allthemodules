<?php

namespace Drupal\visualn_drawing;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\visualn_drawing\Entity\VisualNDrawingInterface;

/**
 * Defines the storage handler class for VisualN Drawing entities.
 *
 * This extends the base storage class, adding required special handling for
 * VisualN Drawing entities.
 *
 * @ingroup visualn_drawing
 */
class VisualNDrawingStorage extends SqlContentEntityStorage implements VisualNDrawingStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(VisualNDrawingInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {visualn_drawing_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {visualn_drawing_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(VisualNDrawingInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {visualn_drawing_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('visualn_drawing_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
