<?php

namespace Drupal\facture;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\facture\Entity\ClientInterface;

/**
 * Defines the storage handler class for Client entities.
 *
 * This extends the base storage class, adding required special handling for
 * Client entities.
 *
 * @ingroup facture
 */
interface ClientStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Client revision IDs for a specific Client.
   *
   * @param \Drupal\facture\Entity\ClientInterface $entity
   *   The Client entity.
   *
   * @return int[]
   *   Client revision IDs (in ascending order).
   */
  public function revisionIds(ClientInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Client author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Client revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\facture\Entity\ClientInterface $entity
   *   The Client entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ClientInterface $entity);

  /**
   * Unsets the language for all Client with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
