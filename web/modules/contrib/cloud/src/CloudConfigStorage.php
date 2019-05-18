<?php

namespace Drupal\cloud;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\cloud\Entity\CloudConfigInterface;

/**
 * Defines the storage handler class for Cloud config entities.
 *
 * This extends the base storage class, adding required special handling for
 * Cloud config entities.
 *
 * @ingroup cloud
 */
class CloudConfigStorage extends SqlContentEntityStorage implements CloudConfigStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CloudConfigInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {cloud_config_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {cloud_config_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(CloudConfigInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {cloud_config_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('cloud_config_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
