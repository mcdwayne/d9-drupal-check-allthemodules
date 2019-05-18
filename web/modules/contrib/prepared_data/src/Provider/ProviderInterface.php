<?php

namespace Drupal\prepared_data\Provider;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\prepared_data\Builder\DataBuilderInterface;
use Drupal\prepared_data\Storage\StorageInterface;

/**
 * Interface for providers of prepared data.
 */
interface ProviderInterface extends PluginInspectionInterface {

  /**
   * Get the key pattern.
   *
   * Parameters are named and enclosed with brackets.
   *
   * @return string
   *   The key pattern.
   */
  public function getKeyPattern();

  /**
   * Get a matching key for the given argument.
   *
   * @param string|array|mixed $argument
   *   Can be a key as string, an associative array of named parameters,
   *   or any kind of object which wants to match.
   *
   * @return string|false
   *   A key as string when the argument
   *   matched the key pattern, FALSE otherwise.
   */
  public function match($argument);

  /**
   * Get the next / closest key which matches to the given key partial.
   *
   * This method is regularly being used
   * for building up prepared data via batch processing.
   *
   * @param string $partial
   *   (Optional) Either a partial or complete key,
   *   which is needed to build up the data.
   * @param bool $reset
   *   (Optional) Whether iteration should be reset.
   *   Default is set to FALSE (no reset).
   *
   * @return string|false
   *   The matched key, or FALSE if no match was found.
   */
  public function nextMatch($partial = NULL, $reset = FALSE);

  /**
   * Set the offset to start at for fetching next matches.
   *
   * @param int $offset
   *   The offset value to set.
   */
  public function setNextMatchOffset($offset);

  /**
   * Checks whether the account may access data for the given argument.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param string|array|mixed $argument
   *   Can be a key as string, an associative array of named parameters,
   *   or any kind of object which wants to match.
   *
   * @return bool
   *   TRUE in case user may access the data, FALSE otherwise.
   */
  public function access(AccountInterface $account, $argument);

  /**
   * Demand prepared data for the given argument.
   *
   * The returned data is not necessarily valid. In case it's not valid
   * anymore, it's being flagged for the next refresh iteration.
   * To enforce data validness, set the $force_valid argument to TRUE.
   *
   * @param string|array|mixed $argument
   *   Can be a key as string, an associative array of named parameters,
   *   or any kind of object which wants to match.
   * @param bool $force_valid
   *   When TRUE, expired data will be refreshed before it's being returned.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The prepared data as wrapped object, or NULL if access
   *   failed or the argument did not match up with the key pattern.
   */
  public function demand($argument, $force_valid = FALSE);

  /**
   * Demand directly refreshed data for the given argument.
   *
   * Usage of this method should only be considered
   * when instantly refreshed data is explicitly required.
   *
   * @param string|array|mixed $argument
   *   Can be a key as string, an associative array of named parameters,
   *   or any kind of object which wants to match.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The prepared data as wrapped object, or NULL if access
   *   failed or the argument did not match up with the key pattern.
   */
  public function demandFresh($argument);

  /**
   * Get the used storage of prepared data.
   *
   * @return \Drupal\prepared_data\Storage\StorageInterface
   */
  public function getDataStorage();

  /**
   * Get the used builder of prepared data.
   *
   * @return \Drupal\prepared_data\Builder\DataBuilderInterface
   */
  public function getDataBuilder();

  /**
   * Get the account being used as current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The associated account as current user.
   */
  public function getCurrentUser();

  /**
   * Set the storage of prepared data to use.
   *
   * @param \Drupal\prepared_data\Storage\StorageInterface $storage
   *   The storage instance to set.
   */
  public function setDataStorage(StorageInterface $storage);

  /**
   * Set the builder of prepared data to use.
   *
   * @param \Drupal\prepared_data\Builder\DataBuilderInterface $builder
   *   The builder instance to set.
   */
  public function setDataBuilder(DataBuilderInterface $builder);

  /**
   * Set the account to be used as current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to be used as current user.
   */
  public function setCurrentUser(AccountInterface $account);

  /**
   * Get state values regards this provider.
   *
   * @return array
   *   An associative array of state values.
   */
  public function getStateValues();

  /**
   * Set the state of this provider by the given values.
   *
   * @param array $values
   *   The state values to set.
   */
  public function setStateValues(array $values);

}
