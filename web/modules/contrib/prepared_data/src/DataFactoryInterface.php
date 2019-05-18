<?php

namespace Drupal\prepared_data;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for prepared data factories.
 */
interface DataFactoryInterface {

  /**
   * Get prepared data for the given argument.
   *
   * The receiving of prepared data includes
   * an access check regards the current user.
   *
   * @param string|array|mixed $argument
   *   Can be a key as string, an associative array of named parameters,
   *   or any kind of object which wants to match.
   * @param bool $force_valid
   *   When TRUE, expired data will be refreshed before it's being returned.
   * @param bool $force_fresh
   *   When set to TRUE, the prepared data will be refreshed
   *   before it's being returned. This should only be considered
   *   when refreshed data is explicitly required.
   *   Default is set to FALSE.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (Optional) When given, the access check will be performed on
   *   this account, instead of the current user.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The prepared data as wrapped object, or NULL if access
   *   failed or the parameters don't match up with any provider.
   */
  public function get($argument, $force_valid = FALSE, $force_fresh = FALSE, AccountInterface $account = NULL);

  /**
   * Get prepared data for the given argument using active processors.
   *
   * This method explicitly sets a given list of processors as active.
   * Additionally, a subset can be defined, and if it's not complete
   * yet, the data will be refreshed using the active processors.
   *
   * The receiving of prepared data includes
   * an access check regards the current user.
   *
   * @param string|array|mixed $argument
   *   Can be a key as string, an associative array of named parameters,
   *   or any kind of object which wants to match.
   * @param array $active_processors
   *   A list of plugin IDs of the processors to be active.
   *   When this argument is empty or is not given,
   *   then all enabled processors will be set as active.
   *   Processors which are not enabled by the user
   *   will not be set as active.
   * @param string|string[] $subset_keys
   *   (Optional) Keys which define a subset, which is expected
   *   to be complete. See PreparedDataInterface::get() regards
   *   how to define subset keys. When the defined subset is
   *   not complete, the data is being completely refreshed
   *   with either all or specified active processors.
   * @param bool $force_valid
   *   When TRUE, expired data will be refreshed before it's being returned.
   * @param bool $force_fresh
   *   When set to TRUE, the prepared data will be refreshed
   *   before it's being returned. This should only be considered
   *   when refreshed data is explicitly required.
   *   Default is set to FALSE.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (Optional) When given, the access check will be performed on
   *   this account, instead of the current user.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|string|array|mixed|null
   *   When the $subset_keys param is given, the subset will be returned.
   *   Otherwise, the prepared data as wrapped object, or NULL if access
   *   failed or the parameters don't match up with any provider.
   */
  public function getProcessed($argument, $active_processors = [], $subset_keys = [], $force_valid = FALSE, $force_fresh = FALSE, AccountInterface $account = NULL);

}
