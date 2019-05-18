<?php

namespace Drupal\ad_entity;

use Drupal\Component\Utility\Xss;

/**
 * Class for collected targeting information.
 */
class TargetingCollection {

  /**
   * An array holding the collected targeting information.
   *
   * @var array
   */
  protected $collected;

  /**
   * TargetingCollection constructor.
   *
   * @param array|string $info
   *   (Optional) Either an array or a JSON-encoded string
   *   holding initial targeting information.
   */
  public function __construct($info = NULL) {
    if (is_array($info)) {
      $this->collected = $info;
    }
    elseif (is_string($info)) {
      $this->collected = json_decode($info, TRUE);
    }
    if (empty($this->collected)) {
      $this->collected = [];
    }
  }

  /**
   * Get the value for the given key.
   *
   * @param string $key
   *   The targeting key.
   *
   * @return string|array|null
   *   The targeting value.
   */
  public function get($key) {
    return !empty($this->collected[$key]) ? $this->collected[$key] : NULL;
  }

  /**
   * Sets the given key to the given value.
   *
   * @param string $key
   *   The targeting key.
   * @param string|array $value
   *   The targeting value.
   */
  public function set($key, $value) {
    $this->collected[$key] = $value;
    $this->unique($key);
  }

  /**
   * Adds the given key-value pair to the current collection.
   *
   * @param string $key
   *   The targeting key.
   * @param string|array $value
   *   The targeting value.
   */
  public function add($key, $value) {
    if (!empty($this->collected[$key])) {
      $having = $this->collected[$key];
      if (!is_array($having)) {
        $having = [$having];
      }
      if (is_array($value)) {
        $this->collected[$key] = array_merge($having, $value);
      }
      else {
        $this->collected[$key] = array_merge($having, [$value]);
      }
    }
    else {
      $this->collected[$key] = $value;
    }
    $this->unique($key);
  }

  /**
   * Removes the given key or key-value pair from the current collection.
   *
   * @param string $key
   *   The targeting key.
   * @param string $value
   *   (Optional) The targeting value to remove, if present.
   */
  public function remove($key, $value = NULL) {
    if (isset($value) && !empty($this->collected[$key])) {
      if (is_array($this->collected[$key])) {
        foreach ($this->collected[$key] as $index => $existing) {
          if ($value == $existing) {
            unset($this->collected[$key][$index]);
            if (empty($this->collected[$key])) {
              // There's no need for keys without values.
              unset($this->collected[$key]);
            }
            elseif (count($this->collected[$key]) === 1) {
              // Transform to a string value.
              $this->collected[$key] = reset($this->collected[$key]);
            }
            else {
              // Reindex the values array.
              $this->collected[$key] = array_values($this->collected[$key]);
            }
          }
        }
      }
      else {
        if ($this->collected[$key] == $value) {
          unset($this->collected[$key]);
        }
      }
    }
    else {
      unset($this->collected[$key]);
    }
  }

  /**
   * Ensures that the targeting key only contains unique values.
   *
   * In case the key only contains one value, the type
   * of it is set to a scalar. Otherwise, it's an array.
   *
   * @param string $key
   *   The targeting key.
   */
  protected function unique($key) {
    if (!isset($this->collected[$key])) {
      return;
    }
    $value = $this->collected[$key];
    if (is_array($value)) {
      $value = array_unique($value);
      if (count($value) === 1) {
        $value = reset($value);
      }
      else {
        // Reindex the values list, to ensure
        // that the index numbering is consistent.
        $value = array_values($value);
      }
    }
    $this->collected[$key] = $value;
  }

  /**
   * Collects targeting info from the given user input.
   *
   * @param string $input
   *   The string which holds the user input.
   *   Keys with multiple values occur multiple times.
   *   Example format: "key1: value1, key2: value2, key2: value3".
   */
  public function collectFromUserInput($input) {
    $pairs = explode(',', $input);
    foreach ($pairs as $pair) {
      $pair = explode(': ', trim($pair));
      $count = count($pair);
      if ($count === 1) {
        // The user might have forgotten to add a space.
        $original = trim($pair[0]);
        $pair = explode(':', $original);
        if (empty($pair[0])) {
          if (empty($pair[1])) {
            // Nothing was given.
            continue;
          }
          // An empty key was given.
          $pair[0] = 'category';
        }
        elseif (empty($pair[1])) {
          // Only a single value was given, which
          // must belong to some kind of key.
          $pair[1] = $pair[0];
          $pair[0] = 'category';
        }
        else {
          foreach ($pair as $part) {
            $first_letter = substr(trim($part), 0, 1);
            if (!ctype_alnum($first_letter)) {
              // The first letter of the key or value is not
              // alphanumeric, thus assume that this value is supposed to be
              // processed later on, e.g. this might be a token.
              $pair[0] = 'category';
              $pair[1] = $original;
              break;
            }
          }
        }
      }
      if ($count > 0) {
        $this->add(trim($pair[0]), trim($pair[1]));
      }
    }
  }

  /**
   * Collects targeting info from the given collection.
   *
   * @param \Drupal\ad_entity\TargetingCollection $collection
   *   The targeting collection to collect from.
   */
  public function collectFromCollection(TargetingCollection $collection) {
    foreach ($collection->toArray() as $key => $value) {
      $this->add($key, $value);
    }
  }

  /**
   * Collects targeting info from the given JSON string.
   *
   * @param string $json
   *   A JSON-encoded string which holds targeting information.
   */
  public function collectFromJson($json) {
    $this->collectFromCollection(new TargetingCollection($json));
  }

  /**
   * Whether the collection is empty or not.
   *
   * @return bool
   *   TRUE if the collection is empty, FALSE otherwise.
   */
  public function isEmpty() {
    return empty($this->collected);
  }

  /**
   * Returns the collected targeting information as an array.
   *
   * @return array
   *   The collection as array.
   */
  public function toArray() {
    return $this->collected;
  }

  /**
   * Returns the collected targeting information as a JSON-encoded string.
   *
   * @return string
   *   The collection as a JSON-encoded string.
   */
  public function toJson() {
    // Encoding result must be the same as TargetingContext::getJsonEncode().
    return json_encode($this->collected, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Returns the collected targeting information as user-editable output.
   *
   * @return string
   *   The user-editable output.
   */
  public function toUserOutput() {
    $pairs = [];
    foreach ($this->collected as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $item) {
          $pairs[] = $key . ': ' . $item;
        }
      }
      else {
        $pairs[] = $key . ': ' . $value;
      }
    }
    return implode(', ', $pairs);
  }

  /**
   * Filters the targeting information.
   *
   * It should be avoided to run the filtering multiple times at the
   * same collection. Otherwise it might lead to double escaping.
   * Instead, use a separate collection object, filter on it, and
   * add it to another collection holding already filtered information.
   *
   * @param string|null $format_id
   *   (Optional) The filter format ID to use for processing.
   *   If not given, any tag will be stripped out by default.
   * @param bool $use_config
   *   When set to TRUE, the method uses the assigned filter format
   *   from the global settings (if any).
   */
  public function filter($format_id = NULL, $use_config = TRUE) {
    if ($use_config) {
      $format_id = \Drupal::config('ad_entity.settings')->get('process_targeting_output');
    }
    $filtered = [];
    foreach ($this->collected as $key => $value) {
      $this->doFilter($key, $format_id);
      if (is_array($value)) {
        foreach ($value as &$item) {
          $this->doFilter($item, $format_id);
        }
      }
      else {
        $this->doFilter($value, $format_id);
      }
      $filtered[$key] = $value;
    }
    $this->collected = $filtered;
  }

  /**
   * Performs filtering on the given text.
   *
   * @param string &$text
   *   The text to filter.
   * @param string|null $format_id
   *   (Optional) The filter format ID to use for processing.
   *   If not given, any tag will be stripped out by default.
   */
  protected function doFilter(&$text, $format_id = NULL) {
    if (isset($format_id)) {
      $text = (string) check_markup($text, $format_id);
    }
    else {
      $text = trim(Xss::filter(strip_tags($text)));
    }
  }

}
