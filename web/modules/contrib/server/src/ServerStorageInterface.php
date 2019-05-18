<?php

namespace Drupal\server;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\server\Entity\ServerInterface;

/**
 * Defines the storage handler class for Server entities.
 *
 * This extends the base storage class, adding required special handling for
 * Server entities.
 *
 * @ingroup server
 */
interface ServerStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Server revision IDs for a specific Server.
   *
   * @param \Drupal\server\Entity\ServerInterface $entity
   *   The Server entity.
   *
   * @return int[]
   *   Server revision IDs (in ascending order).
   */
  public function revisionIds(ServerInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Server author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Server revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\server\Entity\ServerInterface $entity
   *   The Server entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ServerInterface $entity);

  /**
   * Unsets the language for all Server with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
