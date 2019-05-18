<?php

namespace Drupal\merci_line_item;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface MerciLineItemStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Merci Line Item revision IDs for a specific Merci Line Item.
   *
   * @param \Drupal\merci_line_item\Entity\MerciLineItemInterface $entity
   *   The Merci Line Item entity.
   *
   * @return int[]
   *   Merci Line Item revision IDs (in ascending order).
   */
  public function revisionIds(MerciLineItemInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Merci Line Item author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Merci Line Item revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\merci_line_item\Entity\MerciLineItemInterface $entity
   *   The Merci Line Item entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(MerciLineItemInterface $entity);

  /**
   * Unsets the language for all Merci Line Item with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
