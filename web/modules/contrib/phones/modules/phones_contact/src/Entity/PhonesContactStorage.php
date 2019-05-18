<?php

namespace Drupal\phones_contact\Entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\phones_contact\Entity\PhonesContactInterface;

/**
 * Defines the storage handler class for Phones contact entities.
 *
 * This extends the base storage class, adding required special handling for
 * Phones contact entities.
 *
 * @ingroup phones_contact
 */
class PhonesContactStorage extends SqlContentEntityStorage implements PhonesContactStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PhonesContactInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {phones_contact_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {phones_contact_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(PhonesContactInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {phones_contact_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('phones_contact_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
