<?php

/**
 * @file
 * Contains \Drupal\log\LogStorage.
 */

namespace Drupal\log;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the controller class for logs.
 *
 * This extends the base storage class, adding required special handling for
 * log entities.
 */
class LogStorage extends SqlContentEntityStorage implements LogStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(LogInterface $log) {
    return $this->database->query(
      'SELECT vid FROM {log_revision} WHERE id=:id ORDER BY vid',
      array(':id' => $log->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {log_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(LogInterface $log) {
    return $this->database->query('SELECT COUNT(*) FROM {log_field_revision} WHERE id = :id AND default_langcode = 1', array(':id' => $log->id()))->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update('log')
      ->fields(array('type' => $new_type))
      ->condition('type', $old_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('log_revision')
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
