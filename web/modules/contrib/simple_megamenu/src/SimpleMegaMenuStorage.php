<?php

namespace Drupal\simple_megamenu;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simple_megamenu\Entity\SimpleMegaMenuInterface;

/**
 * Defines the storage handler class for Simple mega menu entities.
 *
 * This extends the base storage class, adding required special handling for
 * Simple mega menu entities.
 *
 * @ingroup simple_megamenu
 */
class SimpleMegaMenuStorage extends SqlContentEntityStorage implements SimpleMegaMenuStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SimpleMegaMenuInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {simple_mega_menu_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {simple_mega_menu_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(SimpleMegaMenuInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {simple_mega_menu_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('simple_mega_menu_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
