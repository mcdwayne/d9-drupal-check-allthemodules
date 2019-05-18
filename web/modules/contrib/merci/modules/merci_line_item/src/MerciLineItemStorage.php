<?php

namespace Drupal\merci_line_item;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\merci_line_item\Entity\MerciLineItemInterface;

/**
 * Defines the storage handler class for Merci Line Item entities.
 *
 * This extends the base storage class, adding required special handling for
 * Merci Line Item entities.
 *
 * @ingroup merci_line_item
 */
class MerciLineItemStorage extends SqlContentEntityStorage implements MerciLineItemStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(MerciLineItemInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {merci_line_item_revision} WHERE id=:id ORDER BY vid',
      array(':id' => $entity->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {merci_line_item_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(MerciLineItemInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {merci_line_item_field_revision} WHERE id = :id AND default_langcode = 1', array(':id' => $entity->id()))
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('merci_line_item_revision')
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
