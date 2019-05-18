<?php

namespace Drupal\box;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\box\Entity\BoxInterface;

/**
 * Defines the storage handler class for Box entities.
 *
 * This extends the base storage class, adding required special handling for
 * Box entities.
 *
 * @ingroup box
 */
class BoxStorage extends SqlContentEntityStorage implements BoxStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(BoxInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {box_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {box_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(BoxInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {box_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('box_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

  /**
   * Loads box entity by machine name.
   *
   * @param $machine_name
   *   Machine name to load entities by.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Loaded entity object or FALSE if none exists with given machine name.
   */
  public static function loadByMachineName($machine_name) {
    $entities = \Drupal::entityTypeManager()
      ->getStorage('box')
      ->loadByProperties([
        'machine_name' => $machine_name,
      ]);

    return reset($entities);
  }

}
