<?php

namespace Drupal\homebox;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\homebox\Entity\HomeboxLayoutInterface;

/**
 * Defines the storage handler class for Homebox Layout entities.
 *
 * This extends the base storage class, adding required special handling for
 * Homebox Layout entities.
 *
 * @ingroup homebox
 */
class HomeboxLayoutStorage extends SqlContentEntityStorage implements HomeboxLayoutStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(HomeboxLayoutInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {homebox_layout_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {homebox_layout_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(HomeboxLayoutInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {homebox_layout_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('homebox_layout_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
