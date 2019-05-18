<?php

namespace Drupal\server;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\server\Entity\ServerInterface;

/**
 * Defines the storage handler class for Server entities.
 *
 * This extends the base storage class, adding required special handling for
 * Server entities.
 *
 * @ingroup server
 */
class ServerStorage extends SqlContentEntityStorage implements ServerStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ServerInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {server_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {server_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ServerInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {server_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('server_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
