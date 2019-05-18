<?php

namespace Drupal\css_background;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\css_background\Entity\CssBackgroundEntityInterface;

/**
 * Defines the storage handler class for CSS background entities.
 *
 * This extends the base storage class, adding required special handling for
 * CSS background entities.
 *
 * @ingroup css_background
 */
class CssBackgroundEntityStorage extends SqlContentEntityStorage implements CssBackgroundEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(CssBackgroundEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {css_background_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {css_background_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(CssBackgroundEntityInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {css_background_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('css_background_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
