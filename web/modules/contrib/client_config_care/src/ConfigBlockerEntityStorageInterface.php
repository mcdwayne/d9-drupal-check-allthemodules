<?php

namespace Drupal\client_config_care;

use Drupal\client_config_care\Entity\ConfigBlockerEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Config blocker entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Config blocker entity entities.
 *
 * @ingroup client_config_care
 */
interface ConfigBlockerEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Config blocker entity revision IDs for a specific Config blocker entity.
   *
   * @param \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface $entity
   *   The Config blocker entity entity.
   *
   * @return int[]
   *   Config blocker entity revision IDs (in ascending order).
   */
  public function revisionIds(ConfigBlockerEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Config blocker entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Config blocker entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface $entity
   *   The Config blocker entity entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(ConfigBlockerEntityInterface $entity);

  /**
   * Unsets the language for all Config blocker entity with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
