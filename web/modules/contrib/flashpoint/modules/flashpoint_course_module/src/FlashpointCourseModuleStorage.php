<?php

namespace Drupal\flashpoint_course_module;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface;

/**
 * Defines the storage handler class for Course module entities.
 *
 * This extends the base storage class, adding required special handling for
 * Course module entities.
 *
 * @ingroup flashpoint_course_module
 */
class FlashpointCourseModuleStorage extends SqlContentEntityStorage implements FlashpointCourseModuleStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(FlashpointCourseModuleInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {flashpoint_course_module_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {flashpoint_course_module_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(FlashpointCourseModuleInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {flashpoint_course_module_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('flashpoint_course_module_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
