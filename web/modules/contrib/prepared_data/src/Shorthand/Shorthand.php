<?php

namespace Drupal\prepared_data\Shorthand;

/**
 * Class for shorthand instances of prepared data keys.
 */
class Shorthand implements ShorthandInterface {

  /**
   * The shorthand id.
   *
   * @var string
   */
  protected $id;

  /**
   * The represented data key.
   *
   * @var string
   */
  protected $key;

  /**
   * The represented subset keys.
   *
   * @var array
   */
  protected $subsetKeys;

  /**
   * The data query.
   *
   * @var string
   */
  protected $dataQuery;

  /**
   * Shorthand constructor.
   *
   * @param string $id
   *   The shorthand id.
   * @param string $key
   *   The represented data key.
   * @param string|string[] $subset_keys
   *   (Optional) The represented subset keys.
   */
  public function __construct($id, $key, $subset_keys = []) {
    $this->id = $id;
    $this->key = $key;
    $this->subsetKeys = $subset_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function subsetKeys() {
    return $this->subsetKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataQuery() {
    if (!isset($this->dataQuery)) {
      $this->dataQuery = static::buildDataQuery($this->key(), $this->subsetKeys());
    }
    return $this->dataQuery;
  }

  /**
   * Builds the data key and subset keys as Http query.
   *
   * @param string $key
   *   The data key.
   * @param string|string[] $subset_keys
   *   (Optional) The subset keys.
   *
   * @return string
   *   The query string.
   */
  public static function buildDataQuery($key, $subset_keys = []) {
    $query = ['k' => $key];
    if (!empty($subset_keys)) {
      if (is_array($subset_keys)) {
        sort($subset_keys);
      }
      $query['sk'] = $subset_keys;
    }
    return urldecode(http_build_query($query));
  }

}
