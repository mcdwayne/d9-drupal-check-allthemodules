<?php

namespace Drupal\commerce_installments;

use Drupal\commerce_installments\Entity\InstallmentPlanInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface InstallmentPlanStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Installment Plan revision IDs for a specific Installment Plan.
   *
   * @param \Drupal\commerce_installments\Entity\InstallmentPlanInterface $entity
   *   The Installment Plan entity.
   *
   * @return int[]
   *   Installment Plan revision IDs (in ascending order).
   */
  public function revisionIds(InstallmentPlanInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Installment Plan author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Installment Plan revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\commerce_installments\Entity\InstallmentPlanInterface $entity
   *   The Installment Plan entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(InstallmentPlanInterface $entity);

  /**
   * Unsets the language for all Installment Plan with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
