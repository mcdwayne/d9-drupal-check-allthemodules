<?php

namespace Drupal\patreon_entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\patreon_entity\Entity\PatreonEntityInterface;

/**
 * Defines the storage handler class for Patreon entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Patreon entity entities.
 *
 * @ingroup patreon_entity
 */
class PatreonEntityStorage extends SqlContentEntityStorage implements PatreonEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PatreonEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {patreon_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {patreon_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(PatreonEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {patreon_entity_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('patreon_entity_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
