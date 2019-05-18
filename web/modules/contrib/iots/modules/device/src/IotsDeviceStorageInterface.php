<?php

namespace Drupal\iots_device;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\iots_device\Entity\IotsDeviceInterface;

/**
 * Defines the storage handler class for Device entities.
 *
 * This extends the base storage class, adding required special handling for
 * Device entities.
 *
 * @ingroup iots_device
 */
interface IotsDeviceStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Device revision IDs for a specific Device.
   *
   * @param \Drupal\iots_device\Entity\IotsDeviceInterface $entity
   *   The Device entity.
   *
   * @return int[]
   *   Device revision IDs (in ascending order).
   */
  public function revisionIds(IotsDeviceInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Device author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Device revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\iots_device\Entity\IotsDeviceInterface $entity
   *   The Device entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(IotsDeviceInterface $entity);

  /**
   * Unsets the language for all Device with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
