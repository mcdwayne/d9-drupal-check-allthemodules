<?php

namespace Drupal\trance;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the controller class for trances.
 *
 * This extends the base storage class, adding required special handling for
 * trance entities.
 */
class TranceStorage extends SqlContentEntityStorage implements TranceStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(TranceInterface $trance) {
    /*
     * @todo why does this throw an SQL syntax error?
     *      * return $this->database->query(
     * 'SELECT revision_id FROM {:rev} WHERE uid = :uid ORDER BY revision_id', [
     * ':rev' => $this->entityType->getRevisionDataTable(),
     * ':uid' => $account->id(),
     * ]
     * )->fetchCol();
     */
    $query_fm = new FormattableMarkup('SELECT revision_id FROM {:rev} WHERE id=:id ORDER BY revision_id', [
      ':rev' => $trance->getEntityType()->getRevisionTable(),
    ]);
    return $this->database->query(
      $query_fm->__toString(), [
        ':id' => $trance->id(),
      ]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT revision_id FROM {:rev} WHERE uid = :uid ORDER BY revision_id', [
        ':rev' => $this->entityType->getRevisionDataTable(),
        ':uid' => $account->id(),
      ]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(TranceInterface $trance) {
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
  }

}
