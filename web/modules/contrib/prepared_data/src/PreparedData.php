<?php

namespace Drupal\prepared_data;

use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\prepared_data\Serialization\DataSerializationTrait;
use Drupal\prepared_data\Shorthand\ShorthandTrait;

/**
 * Class wrapper implementation for prepared data.
 */
class PreparedData implements PreparedDataInterface {

  use DataSerializationTrait;
  use ShorthandTrait;

  /**
   * The key used as identifier for the prepared data.
   *
   * @var string
   */
  protected $key;

  /**
   * The expiration timestamp regards the data validness.
   *
   * @var int
   */
  protected $expires;

  /**
   * The array representation of the prepared data.
   *
   * @var array
   */
  protected $data;

  /**
   * Contains arbitrary information required to generate prepared data.
   *
   * @var array
   */
  protected $info;

  /**
   * The last time the data was updated, if known.
   *
   * @var int|NULL
   */
  protected $lastUpdated;

  /**
   * The base url where prepared data can be received.
   *
   * @var \Drupal\Core\Url
   */
  protected $baseUrl;

  /**
   * Whether this data should be refreshed or not.
   *
   * @var bool
   */
  protected $shouldRefresh = FALSE;

  /**
   * Whether this data should be deleted or not.
   *
   * @var bool
   */
  protected $shouldDelete = FALSE;

  /**
   * PreparedData constructor.
   *
   * @param mixed $data
   *   (Optional) Already known values of prepared data,
   *   either as an array or as encoded string.
   * @param string $key
   *   (Optional) If known, the key which identifies the data.
   * @param int $last_updated
   *   (Optional) If known, the last time when the data was updated.
   * @param int $expires
   *   (Optional) If known, the validness expiration time.
   * @param bool $should_refresh
   *   (Optional) If known, whether the data should be refreshed or not.
   */
  public function __construct($data = NULL, $key = NULL, $last_updated = NULL, $expires = NULL, $should_refresh = NULL) {
    if (isset($data)) {
      if (is_array($data)) {
        $this->data = $data;
      }
      elseif (is_string($data)) {
        // Keep the encoded representation of the data,
        // as long as it's not needed in any other representation.
        $this->encoded = $data;
      }
    }
    if (isset($key)) {
      $this->key($key);
    }
    if (isset($last_updated)) {
      $this->lastUpdated = $last_updated;
    }
    else {
      $this->lastUpdated = NULL;
    }
    if (isset($expires)) {
      $this->expires($expires);
    }
    if (TRUE === $should_refresh) {
      $this->shouldRefresh(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function key($key = NULL) {
    if (isset($key)) {
      $this->key = $key;
    }
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function expires($timestamp = NULL) {
    if (isset($timestamp)) {
      $this->expires = $timestamp;
      if ($timestamp < time()) {
        $this->shouldRefresh(TRUE);
      }
      else {
        $this->shouldRefresh(FALSE);
      }
    }
    elseif (!isset($this->expires)) {
      $this->expires = time();
      $this->shouldRefresh(TRUE);
    }
    return $this->expires;
  }

  /**
   * {@inheritdoc}
   */
  public function lastUpdated() {
    return $this->lastUpdated;
  }

  /**
   * {@inheritdoc}
   */
  public function &data(array $data = NULL) {
    if (isset($data)) {
      $this->data = $data;
      // Prevent inconsistent data states
      // by removing the encoded representation.
      unset($this->encoded);
    }
    elseif (!isset($this->data)) {
      // Decode the data when it's needed.
      if (isset($this->encoded)) {
        if ($decoded = $this->getSerializer()->decode($this->encoded)) {
          $this->data($decoded->data());
        }
      }
      else {
        $this->data = [];
      }
    }
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function &info(array $info = NULL) {
    if (isset($info)) {
      $this->info = $info;
    }
    elseif (!isset($this->info)) {
      $this->info = [];
    }
    return $this->info;
  }

  /**
   * {@inheritdoc}
   */
  public function get($subset_keys = [], $fallback_value = NULL) {
    if (empty($subset_keys)) {
      return $this->data();
    }
    $data = &$this->data();
    $subset = [];
    $return_direct = FALSE;
    if (is_string($subset_keys)) {
      $return_direct = TRUE;
      $subset_keys = [$subset_keys];
    }
    foreach ($subset_keys as $sk) {
      $is_string = is_string($sk);
      if ($is_string && isset($data[$sk])) {
        $subset[$sk] = $data[$sk];
      }
      elseif (($is_string && strpos($sk, ':') !== FALSE) || is_array($sk)) {
        // This one is a nested selection.
        $sk_parents = $is_string ? explode(':', $sk) : $sk;
        $sk_exists = FALSE;
        $sk_value = NestedArray::getValue($data, $sk_parents, $sk_exists);
        if (!$sk_exists) {
          $sk_value = $fallback_value;
        }
        if ($return_direct) {
          return $sk_value;
        }
        NestedArray::setValue($subset, $sk_parents, $sk_value);
      }
      else {
        $subset[$sk] = $fallback_value;
      }
    }
    if ($return_direct) {
      return reset($subset);
    }
    return $subset;
  }

  /**
   * {@inheritdoc}
   */
  public function set($subset_key, $value) {
    $data = &$this->data();
    $data[$subset_key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $data = $this->data();
    return empty($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl($subset_keys = [], $use_shorthand = TRUE) {
    $key = $this->key();
    $base_url = $this->getBaseUrl();
    if (!isset($key, $base_url)) {
      return NULL;
    }

    $url = clone $base_url;
    if (!empty($key)) {
      if (empty($query = $url->getOption('query'))) {
        $query = [];
      }
      if ($use_shorthand) {
        $shorthand = $this->shorthands()->getFor($key, $subset_keys);
        $query['s'] = $shorthand->id();
      }
      else {
        $query['k'] = $key;
        if (!empty($subset_keys)) {
          if (is_array($subset_keys)) {
            sort($subset_keys);
          }
          $query['sk'] = $subset_keys;
        }
      }
      $url->setOption('query', $query);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRefresh($should = NULL) {
    if (isset($should)) {
      $this->shouldRefresh = $should;
    }
    return $this->shouldRefresh;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldDelete($should = NULL) {
    if (isset($should)) {
      $this->shouldDelete = $should;
    }
    return $this->shouldDelete;
  }

  /**
   * Get the base url for prepared data.
   *
   * @return \Drupal\Core\Url
   */
  protected function getBaseUrl() {
    if (!isset($this->baseUrl)) {
      $this->baseUrl = Url::fromRoute('prepared_data.get')
        ->setAbsolute(FALSE);
    }
    return $this->baseUrl;
  }

}
