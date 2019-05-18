<?php

namespace Drupal\config_actions;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\NestedArray;

/**
 * Perform transformations on data needed for config_actions plugins
 */
class ConfigActionsTransform {

  /**
   * Recursive helper function to walk a tree and add/change a value
   *
   * @param array $tree : An array passed by reference to which we will add
   * @param array $path : An array of keys to walk to find the point to insert
   * @param $value : the value to insert
   */
  protected static function tree_change_data(array &$tree, array $path, $value) {
    // If no path is given, loop through the value array and set top-level items
    if (empty($path) && is_array($value)) {
      foreach ($value as $key => $item) {
        static::tree_change_data($tree, [$key], $item);
      }
      return;
    }

    $key = array_shift($path);
    if (!empty($path)) {
      //if we do not have a value set it
      if (!array_key_exists($key, $tree)) {
        $tree[$key] = [];
      }
      static::tree_change_data($tree[$key], $path, $value);
    }
    else {
      $tree[$key] = $value;
    }
  }

  /**
   * Recursive helper function to walk a tree and prune it.
   *
   * @param array $tree : A Array passed by reference that we will prune
   * @param array $path : An array of keys to walk to find the point to prune
   * @param bool $prune_empty : If true then unset the data key, otherwise just
   *   clear the data based on its existing value.
   */
  protected static function tree_delete_data(array &$tree, array $path, $prune_empty = FALSE) {
    $key = array_shift($path);
    //If we have farther to walk keep walking
    if (!empty($path)) {
      static::tree_delete_data($tree[$key], $path, $prune_empty);
      //if we have an empty branch we prune
      if ($prune_empty && empty($tree[$key])) {
        unset($tree[$key]);
      }
    }
    elseif (empty($key)) {
      $tree = NULL;
    }
    //If we are at the end we prune or clear the value
    elseif (isset($tree[$key])) {
      $old = $tree[$key];
      if ($prune_empty) {
        unset($tree[$key]);
      }
      elseif (is_string($old)) {
          $tree[$key] = '';
      }
      elseif (is_array($old)) {
        $tree[$key] = [];
      }
      elseif (is_object($old)) {
        $tree[$key] = NULL;
      }
      elseif (is_numeric($old)) {
        $tree[$key] = 0;
      }
      elseif (is_bool($old)) {
        $tree[$key] = FALSE;
      }
      else {
        $tree[$key] = NULL;
      }
    }
  }

  /**
   * Recursively string replace the values of an array.
   * @param string|array $search
   *   Optional search values for operating on BOTH keys and values
   * @param string|array $replace
   *   Optional replace keys for operating on BOTH keys and values
   * @param array $data
   *   Data to be altered
   * @param string|array $search_keys
   *   Optional search values for operating on JUST keys
   * @param string|array $replace_keys
   *   Optional replace keys for operating on JUST keys
   * @return array
   */
  public static function replaceTree($data, $search = '', $replace = '',
                                     $search_keys = '', $replace_keys = '') {
    if (!is_array($data)) {
      if (is_string($data)) {
        // Regular string replace.
        $matched = FALSE;
        // First check for exact variable matches.
        foreach ($search as $search_key => $search_value) {
          if ($search_value === $data) {
            // Replace exact match with the exact replacement value.
            // Fixes issue with integer vs string replacements.
            $data = $replace[$search_key];
            $matched = TRUE;
            break;
          }
        }
        if (!$matched) {
          // No exact match, so perform generic string replacements.
          $data = str_replace($search, $replace, $data);
        }
      }
      return $data;
    }

    $newArr = array();
    foreach ($data as $key => $value) {
      // Recurse
      if (!empty($search_keys)) {
        $key = str_replace($search_keys, $replace_keys, $key);
      }
      $newArr[$key] = static::replaceTree($value, $search, $replace, $search_keys, $replace_keys);
    }
    return $newArr;
  }

  /**
   * Replace tokens within yaml data and return the resulting array
   *
   * @param mixed $item
   *   $item can be a string of yml data to be processed
   *   if $item is an array, each element is processed
   * @param array $replacements
   *   key/value array with search/replace strings.
   * @param array $key_replacements
   *   optional key/value array with search/replace strings for item keys.
   * @return mixed
   */
  public static function replace($item, array $replacements, array $key_replacements = []) {
    $tree = static::replaceTree($item,
      array_keys($replacements), array_values($replacements),
      array_keys($key_replacements), array_values($key_replacements));
    if (is_string($tree) && !is_string($item)) {
      $tree = Yaml::decode($tree);
    }
    return $tree;
  }

  /**
   * Walk a tree and add a value at the end of the path
   *
   * @param array $tree : A base on which to add the path
   * @param array $path : An array of keys to walk in the tree
   * @param $value : what ever is to be add at the end of the path
   * @param bool $append TRUE to merge with existing data, FALSE to replace
   *
   * @return array: A copy of the $tree with the new value added
   */
  public static function add(array $tree, array $path, $value, $append = FALSE) {
    if ($append) {
      $current_value = static::read($tree, $path);
      if (!isset($current_value) && !is_array($value)) {
        $value = [$value];
      }
      elseif (is_array($current_value)) {
        if (!is_array($value)) {
          $value = array_merge($current_value, [$value]);
        }
        else {
          $value = NestedArray::mergeDeepArray([$current_value, $value], TRUE);
        }
      }
    }
    static::tree_change_data($tree, $path, $value);
    return $tree;
  }

  /**
   * walk a tree and return what is at the end of the path
   *
   * @param array $tree : the tree to walk
   * @param array $path : an array of keys to walk in the tree
   *
   * @return mixed: what ever is at the end of the path through the tree
   */
  public static function read(array $tree, array $path) {
    return array_reduce($path, function ($carry, $key) {
      return isset($carry[$key]) ? $carry[$key] : NULL;
    }, $tree);
  }

  /**
   * Walk a tree and add change the value at the end of a path
   *
   * @param array $tree : A base on which to add the path
   * @param array $path : An array of keys to walk in the tree
   * @param $value : what ever is to be add at the end of the path
   *
   * @return array: A copy of the $tree with the new value added
   */
  public static function change(array $tree, array $path, $value) {
    return static::add($tree, $path, $value);
  }

  /**
   * Walk a tree and remove a path
   *
   * @param array $tree : A base on which to add the path
   * @param array $path : An array of keys to walk in the tree
   * @param bool $prune_empty : If true then unset the data key, otherwise just
   *   clear the data based on its existing value.
   *
   * @return array: A copy of the $tree with the path removed
   */
  public static function delete(array $tree, array $path, $prune_empty = FALSE) {
    static::tree_delete_data($tree, $path, $prune_empty);
    return $tree;
  }

  /**
   * Return TRUE if arr is an associative array.
   * @param mixed $arr
   * @return bool
   */
  public static function isAssoc($arr) {
    if (!is_array($arr) || array() === $arr) {
      return FALSE;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  /**
   * Test a string against a variable wildcard pattern.
   *
   * @param string $pattern
   *   A string pattern with @var@ wildcards, and @var__value@ replacements
   * @param string $source
   *   The source string to test.
   * @param array $data
   *   Optional list of property values to test when using
   *   @property__value@ in pattern
   * @return array
   *   Returns a list of matching vars and values, or returns empty array if
   *   source doesn't match the pattern.
   */
  public static function parseWildcards($pattern, $source, $data = []) {
    $result = [];

    // Pattern example: "@name@.@type@"
    // Get the variables from the pattern.
    $vars = [];
    preg_match_all('/\@[a-zA-Z0-9_\-]+?\@/', $pattern, $matches);
    $matches = !empty($matches) ? $matches[0] : [];
    foreach ($matches as $match) {
      // Test property__value validation in supplied replacements
      if (strpos($match, '__') > 0) {
        list($property, $value) = explode('__', str_replace('@', '', $match));
        // If $data is provided AND
        // either property isn't found, or property is a string and doesn't match
        // Then fail test.
        if (!empty($data) &&
          (!isset($data[$property])
            || (is_string($data[$property]) && ($data[$property] !== $value)))) {
          // Found a @property__value@ validation that failed, so no match
          return [];
        }
        // Remove the validation check from pattern but set variable value
        $pattern = str_replace($match, '', $pattern);
        $result['@' . $property . '@'] = $value;
        continue;
      }
      $vars[] = $match;
    }
    // $vars is array of variable names: [ '@name@', '@type@' ]

    // Convert pattern to regex.
    // First escape special chars.
    $regEx = preg_replace('/([^a-zA-Z0-9_\@\-])/', '\\\$1', $pattern);
    // Next, replace variables with regex wildcards.
    $regEx = '/^' . preg_replace('/\@[a-zA-Z0-9_\-]+?\@/', '([a-zA-Z0-9_\-]+?)', $regEx) . '$/';
    // $regEx is like /^([a-zA-Z0-9_]+?)\.([a-zA-Z0-9_]+?)$/

    // Run the regex to extract the data corresponding to the variables.
    preg_match($regEx, $source, $matches);
    // $matches should now contain array like: [ 'node.blog', 'node', 'blog' ]

    // Loop through matches and assign values to variables
    if (!empty($matches) && (count($matches) == count($vars) + 1)) {
      array_shift($matches);
      foreach ($matches as $key => $match) {
        $result[$vars[$key]] = $match;
      }
    }
    else {
      // Didn't match all the vars.
      $result = [];
    }
    return $result;
  }

}
