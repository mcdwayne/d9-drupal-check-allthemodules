<?php

namespace Drupal\commerce_installments;

use Drupal\commerce_installments\Entity\InstallmentPlanInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Installment Plan entities.
 *
 * This extends the base storage class, adding required special handling for
 * Installment Plan entities.
 *
 * @ingroup commerce_installments
 */
class InstallmentPlanStorage extends SqlContentEntityStorage implements InstallmentPlanStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(InstallmentPlanInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {installment_plan_revision} WHERE plan_id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {installment_plan_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(InstallmentPlanInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {installment_plan_field_revision} WHERE plan_id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('installment_plan_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
