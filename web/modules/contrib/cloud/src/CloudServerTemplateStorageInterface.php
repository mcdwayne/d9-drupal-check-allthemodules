<?php

namespace Drupal\cloud;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\cloud\Entity\CloudServerTemplateInterface;

/**
 * Defines the storage handler class for Cloud Server Template entities.
 *
 * This extends the base storage class, adding required special handling for
 * Cloud Server Template entities.
 *
 * @ingroup cloud_server_template
 */
interface CloudServerTemplateStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Cloud Server Template revision IDs.
   *
   * @param \Drupal\cloud\Entity\CloudServerTemplateInterface $entity
   *   The Cloud Server Template entity.
   *
   * @return int[]
   *   Cloud Server Template revision IDs (in ascending order).
   */
  public function revisionIds(CloudServerTemplateInterface $entity);

  /**
   * Gets a list of revision IDs given an Cloud Server Template author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Cloud Server Template revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\cloud\Entity\CloudServerTemplateInterface $entity
   *   The Cloud Server Template entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(CloudServerTemplateInterface $entity);

  /**
   * Unsets the language for all Cloud Server Template with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
