<?php

namespace Drupal\prepared_data;

/**
 * Interface for working with prepared data.
 */
interface PreparedDataInterface {

  /**
   * Get or set the identifier for the prepared data.
   *
   * @param string|null $key
   *   When given, the key will be set.
   *
   * @return mixed
   */
  public function key($key = NULL);

  /**
   * Get or set the expiry timestamp regards data validness.
   *
   * @param int|NULL $timestamp
   *   When given, the expiry will be set to this stamp.
   *
   * @return int
   *   The current expiry as timestamp.
   */
  public function expires($timestamp = NULL);

  /**
   * The time when the data was last updated.
   *
   * @return int|NULL
   *   The timestamp or NULL if not known.
   */
  public function lastUpdated();

  /**
   * Get or set the prepared data values.
   *
   * @param array|NULL $data
   *   When given, the data will be set.
   *
   * @return array
   *   The prepared data values as array.
   */
  public function &data(array $data = NULL);

  /**
   * Get or set arbitrary information regards the given data.
   *
   * This array holds arbitrary information generated and
   * needed by data processor plugins. The information won't
   * be stored along with the data, it will always
   * be generated on runtime instead.
   *
   * @param array|NULL $info
   *   When given, the information will be set.
   *
   * @return array
   *   The information values as array.
   */
  public function &info(array $info = NULL);

  /**
   * Get a defined subset of prepared data values.
   *
   * @param string|string[] $subset_keys
   *   As string: One subset key to return its data subset directly.
   *   As array: A list of subset keys to return the subsets as nested array.
   *   When the argument is empty, all data values will be returned.
   *   Example for two subset keys: ['level1', 'level1:level2']
   *   would return ['level1' => [...], ['level1' => ['level2' => [...]]]].
   * @param mixed $fallback_value
   *   A default value to use as fallback when one of
   *   the subset keys does not exist in the data values.
   *
   * @return string|array|mixed
   *   The defined subset of prepared data values, if available.
   *   When $subset_keys was an array, the return is an array
   *   keyed by the given subset keys. Otherwise, if one subset key
   *   was given as string, the subset will be returned directly.
   */
  public function get($subset_keys = [], $fallback_value = NULL);

  /**
   * Set the value of a data subset.
   *
   * @param string $subset_key
   *   The subset key.
   * @param string|string[] $value
   *   The value to set.
   */
  public function set($subset_key, $value);

  /**
   * Encodes the prepared data values to a string.
   *
   * Note that the encoding only handles data values.
   * The returned string neither contains any generated
   * meta information, expiry nor any key identifier.
   *
   * @param string|string[] $subset_keys
   *   When given, only the subset will be encoded and returned.
   *   Have a look at ::get() for an explanation about subset keys.
   *
   * @return string|NULL
   *   The encoding result as string.
   */
  public function encode($subset_keys = []);

  /**
   * Whether the prepared data values are all empty or not.
   *
   * @return bool
   *   TRUE if empty, FALSE otherwise.
   */
  public function isEmpty();

  /**
   * Get the url where the prepared data is available.
   *
   * @param string|string[] $subset_keys
   *   When given, the Url will be build
   *   to deliver only a subset of data values.
   *   Subset keys can only be assigned when the key
   *   identifier of this data is known.
   *   Have a look at ::get() for an explanation about subset keys.
   * @param bool $use_shorthand
   *   (Optional) When TRUE, a shorthand will be used to build the url.
   *
   * @return \Drupal\Core\Url|null
   *   The Url if one is available, NULL otherwise.
   */
  public function getUrl($subset_keys = [], $use_shorthand = TRUE);

  /**
   * Get or set the flag whether this data should be refreshed.
   *
   * @param bool|null $should
   *   (Optional) Set as boolean to flag it for being refreshed.
   *
   * @return bool
   *   TRUE in case it should be refreshed, FALSE otherwise.
   */
  public function shouldRefresh($should = NULL);

  /**
   * Get or set the flag whether this data should be deleted.
   *
   * @param bool|null $should
   *   (Optional) Set as boolean to flag it for being deleted (TRUE).
   *
   * @return bool
   *   TRUE in case it should be deleted, FALSE otherwise.
   */
  public function shouldDelete($should = NULL);

}
