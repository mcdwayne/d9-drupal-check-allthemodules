<?php

namespace Drupal\facture;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\facture\Entity\ClientInterface;

/**
 * Defines the storage handler class for Client entities.
 *
 * This extends the base storage class, adding required special handling for
 * Client entities.
 *
 * @ingroup facture
 */
class ClientStorage extends SqlContentEntityStorage implements ClientStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ClientInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {client_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {client_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ClientInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {client_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('client_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
