<?php

namespace Drupal\assembly;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\assembly\Entity\AssemblyInterface;

/**
 * Defines the storage handler class for Assembly entities.
 *
 * This extends the base storage class, adding required special handling for
 * Assembly entities.
 *
 * @ingroup assembly
 */
class AssemblyStorage extends SqlContentEntityStorage implements AssemblyStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(AssemblyInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {assembly_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {assembly_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(AssemblyInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {assembly_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('assembly_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
