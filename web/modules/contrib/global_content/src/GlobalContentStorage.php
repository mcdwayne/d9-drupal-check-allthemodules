<?php

namespace Drupal\global_content;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\global_content\Entity\GlobalContentInterface;

/**
 * Defines the storage handler class for Global Content entities.
 *
 * This extends the base storage class, adding required special handling for
 * Global Content entities.
 *
 * @ingroup global_content
 */
class GlobalContentStorage extends SqlContentEntityStorage implements GlobalContentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(GlobalContentInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {global_content_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {global_content_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(GlobalContentInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {global_content_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('global_content_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
