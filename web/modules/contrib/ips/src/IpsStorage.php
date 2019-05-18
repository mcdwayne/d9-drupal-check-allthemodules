<?php

namespace Drupal\ips;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\ips\Entity\IpsInterface;

/**
 * Defines the storage handler class for Ips entities.
 *
 * This extends the base storage class, adding required special handling for
 * Ips entities.
 *
 * @ingroup ips
 */
class IpsStorage extends SqlContentEntityStorage implements IpsStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(IpsInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {ips_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {ips_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(IpsInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {ips_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('ips_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
