<?php

namespace Drupal\business_rules;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\business_rules\Entity\ScheduleInterface;

/**
 * Defines the storage handler class for Schedule entities.
 *
 * This extends the base storage class, adding required special handling for
 * Schedule entities.
 *
 * @ingroup business_rules
 */
class ScheduleStorage extends SqlContentEntityStorage implements ScheduleStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(ScheduleInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {br_schedule_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {br_schedule_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(ScheduleInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {br_schedule_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('schedule_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
