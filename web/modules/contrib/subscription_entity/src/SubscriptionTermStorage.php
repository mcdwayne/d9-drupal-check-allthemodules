<?php

namespace Drupal\subscription_entity;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\subscription_entity\Entity\SubscriptionTermInterface;

/**
 * Defines the storage handler class for Subscription Term entities.
 *
 * This extends the base storage class, adding required special handling for
 * Subscription Term entities.
 *
 * @ingroup subscription
 */
class SubscriptionTermStorage extends SqlContentEntityStorage implements SubscriptionTermStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SubscriptionTermInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {subscription_term_revision} WHERE id=:id ORDER BY vid',
      array(':id' => $entity->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {subscription_term_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(SubscriptionTermInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {subscription_term_field_revision} WHERE id = :id AND default_langcode = 1', array(':id' => $entity->id()))
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('subscription_term_revision')
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
