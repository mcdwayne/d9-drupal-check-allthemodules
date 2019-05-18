<?php

namespace Drupal\micro_site;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Defines the storage handler class for Site entities.
 *
 * This extends the base storage class, adding required special handling for
 * Site entities.
 *
 * @ingroup micro_site
 */
class SiteStorage extends SqlContentEntityStorage implements SiteStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SiteInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {site_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {site_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(SiteInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {site_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('site_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
