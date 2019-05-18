<?php

namespace Drupal\fragments;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\fragments\Entity\FragmentInterface;

/**
 * Defines the storage handler class for fragment entities.
 *
 * This extends the base storage class, adding required special handling for
 * fragment entities.
 *
 * @ingroup fragments
 */
class FragmentStorage extends SqlContentEntityStorage implements FragmentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(FragmentInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {fragment_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {fragment_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(FragmentInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {fragment_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('fragment_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
