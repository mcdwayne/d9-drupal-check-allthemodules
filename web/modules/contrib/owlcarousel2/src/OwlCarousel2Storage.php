<?php

namespace Drupal\owlcarousel2;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\owlcarousel2\Entity\OwlCarousel2Interface;

/**
 * Defines the storage handler class for OwlCarousel2 entities.
 *
 * This extends the base storage class, adding required special handling for
 * OwlCarousel2 entities.
 *
 * @ingroup owlcarousel2
 */
class OwlCarousel2Storage extends SqlContentEntityStorage implements OwlCarousel2StorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(OwlCarousel2Interface $entity) {
    return $this->database->query(
      'SELECT vid FROM {owlcarousel2_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {owlcarousel2_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(OwlCarousel2Interface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {owlcarousel2_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('owlcarousel2_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
