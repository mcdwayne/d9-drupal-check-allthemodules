<?php

namespace Drupal\iots_device;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\iots_device\Entity\IotsDeviceInterface;

/**
 * Defines the storage handler class for Device entities.
 *
 * This extends the base storage class, adding required special handling for
 * Device entities.
 *
 * @ingroup iots_device
 */
class IotsDeviceStorage extends SqlContentEntityStorage implements IotsDeviceStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(IotsDeviceInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {iots_device_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {iots_device_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(IotsDeviceInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {iots_device_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('iots_device_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
