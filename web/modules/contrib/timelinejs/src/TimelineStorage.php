<?php

namespace Drupal\timelinejs;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\timelinejs\Entity\TimelineInterface;

/**
 * Defines the storage handler class for Timeline entities.
 *
 * This extends the base storage class, adding required special handling for
 * Timeline entities.
 *
 * @ingroup timelinejs
 */
class TimelineStorage extends SqlContentEntityStorage implements TimelineStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(TimelineInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {timeline_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {timeline_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(TimelineInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {timeline_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('timeline_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
