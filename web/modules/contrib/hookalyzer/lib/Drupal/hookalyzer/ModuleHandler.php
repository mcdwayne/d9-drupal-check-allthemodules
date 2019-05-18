<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\ModuleHandler.
 */

namespace Drupal\hookalyzer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\CachedModuleHandler as CoreModuleHandler;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\hookalyzer\Diff\Diff;
use Drupal\hookalyzer\Diff\DiffCollection;
use Drupal\hookalyzer\Diff\Iterator\Differ;

/**
 * Same as the core CachedModuleHandler, but with fun hook analytics!
 */
class ModuleHandler extends CoreModuleHandler {

  protected $alterLog = array();

  /**
   * @var Differ
   */
  protected $alterIterator;

  public function __construct(array $module_list = array(), KeyValueStoreInterface $state, CacheBackendInterface $bootstrap_cache) {
    parent::__construct($module_list, $state, $bootstrap_cache);
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Most of the time, $type is passed as a string, so for performance,
    // normalize it to that. When passed as an array, usually the first item in
    // the array is a generic type, and additional items in the array are more
    // specific variants of it, as in the case of array('form', 'form_FORM_ID').
    if (is_array($type)) {
      $cid = implode(',', $type);
      $extra_types = $type;
      $type = array_shift($extra_types);
      // Allow if statements in this function to use the faster isset() rather
      // than !empty() both when $type is passed as a string, or as an array with
      // one item.
      if (empty($extra_types)) {
        unset($extra_types);
      }
    }
    else {
      $cid = $type;
    }

    // For now, just keep the record of the last run of any alter hook; good enough for a first step
    if (isset($this->alterLog[$cid])) {
      unset($this->alterLog[$cid]);
    }

    // Some alter hooks are invoked many times per page request, so store the
    // list of functions to call, and on subsequent calls, iterate through them
    // quickly.
    if (!isset($this->alterFunctions[$cid])) {
      $this->alterFunctions[$cid] = array();
      $hook = $type . '_alter';
      $modules = $this->getImplementations($hook);
      if (!isset($extra_types)) {
        // For the more common case of a single hook, we do not need to call
        // function_exists(), since $this->getImplementations() returns only modules with
        // implementations.
        foreach ($modules as $module) {
          $this->alterFunctions[$cid][] = $module . '_' . $hook;
        }
      }
      else {
        // For multiple hooks, we need $modules to contain every module that
        // implements at least one of them.
        $extra_modules = array();
        foreach ($extra_types as $extra_type) {
          $extra_modules = array_merge($extra_modules, $this->getImplementations($extra_type . '_alter'));
        }
        // If any modules implement one of the extra hooks that do not implement
        // the primary hook, we need to add them to the $modules array in their
        // appropriate order. $this->getImplementations() can only return ordered
        // implementations of a single hook. To get the ordered implementations
        // of multiple hooks, we mimic the $this->getImplementations() logic of first
        // ordering by $this->getModuleList(), and then calling
        // $this->alter('module_implements').
        if (array_diff($extra_modules, $modules)) {
          // Merge the arrays and order by getModuleList().
          $modules = array_intersect(array_keys($this->moduleList), array_merge($modules, $extra_modules));
          // Since $this->getImplementations() already took care of loading the necessary
          // include files, we can safely pass FALSE for the array values.
          $implementations = array_fill_keys($modules, FALSE);
          // Let modules adjust the order solely based on the primary hook. This
          // ensures the same module order regardless of whether this if block
          // runs. Calling $this->alter() recursively in this way does not result
          // in an infinite loop, because this call is for a single $type, so we
          // won't end up in this code block again.
          $this->alter('module_implements', $implementations, $hook);
          $modules = array_keys($implementations);
        }
        foreach ($modules as $module) {
          // Since $modules is a merged array, for any given module, we do not
          // know whether it has any particular implementation, so we need a
          // function_exists().
          $function = $module . '_' . $hook;
          if (function_exists($function)) {
            $this->alterFunctions[$cid][] = $function;
          }
          foreach ($extra_types as $extra_type) {
            $function = $module . '_' . $extra_type . '_alter';
            if (function_exists($function)) {
              $this->alterFunctions[$cid][] = $function;
            }
          }
        }
      }
      // Allow the theme to alter variables after the theme system has been
      // initialized.
      global $theme, $base_theme_info;
      if (isset($theme)) {
        $theme_keys = array();
        foreach ($base_theme_info as $base) {
          $theme_keys[] = $base->name;
        }
        $theme_keys[] = $theme;
        foreach ($theme_keys as $theme_key) {
          $function = $theme_key . '_' . $hook;
          if (function_exists($function)) {
            $this->alterFunctions[$cid][] = $function;
          }
          if (isset($extra_types)) {
            foreach ($extra_types as $extra_type) {
              $function = $theme_key . '_' . $extra_type . '_alter';
              if (function_exists($function)) {
                $this->alterFunctions[$cid][] = $function;
              }
            }
          }
        }
      }
    }

    $this->processAlter('(init)', $cid, $data, $context1, $context2);
    foreach ($this->alterFunctions[$cid] as $function) {
      $function($data, $context1, $context2);
      $this->processAlter($function, $cid, $data, $context1, $context2);
    }

    $this->alterIterator = NULL;
  }

  protected function processAlter($caller, $cid, $data, $context1, $context2) {
    if (!is_array($data)) {
      return;
    }

    $this->alterLog[$cid][$caller] = $coll = new DiffCollection($caller);

    if ($this->alterIterator === NULL) {
      $this->alterIterator = new Differ(new \ArrayIterator($data));
    }
    else {
      $this->alterIterator->setNextIterator(new \ArrayIterator($data));
    }

    foreach ($this->alterIterator as $key => $diff) {
      $coll->addDiff($key, $diff);
    }
  }

  public function invokeAll($hook, $args = array()) {
    $return = array();
    $implementations = $this->getImplementations($hook);
    foreach ($implementations as $module) {
      $function = $module . '_' . $hook;
      if (function_exists($function)) {
        $result = call_user_func_array($function, $args);
        if (isset($result) && is_array($result)) {
          $return = NestedArray::mergeDeep($return, $result);
        }
        elseif (isset($result)) {
          $return[] = $result;
        }
      }
    }

    return $return;
  }

  public function getAlterLog() {
    return $this->alterLog;
  }
}