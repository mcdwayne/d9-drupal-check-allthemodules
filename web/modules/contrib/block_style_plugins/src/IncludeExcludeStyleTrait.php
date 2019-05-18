<?php

namespace Drupal\block_style_plugins;

/**
 * Provides a helper for determining whether to include or exclude a plugin.
 */
trait IncludeExcludeStyleTrait {

  /**
   * Determine whether a style should be allowed.
   *
   * @param string $plugin_id
   *   The ID of the block being checked.
   * @param array $plugin_definition
   *   A list of definitions for a block_style_plugin which could have 'include'
   *   or 'exclude' as keys.
   *
   * @return bool
   *   Return True if the block should show the styles.
   */
  public function allowStyles($plugin_id, array $plugin_definition) {
    if ($this->includeOnly($plugin_id, $plugin_definition) && !$this->exclude($plugin_id, $plugin_definition)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Exclude styles from appearing on blocks.
   *
   * Determine if configuration should be excluded from certain blocks when a
   * block plugin id or block content type is passed from a plugin.
   *
   * @param string $plugin_id
   *   The ID of the block being checked.
   * @param array $plugin_definition
   *   A list of definitions for a block_style_plugins which could have the key
   *   'exclude' set as a list of block plugin ids to disallow.
   *
   * @return bool
   *   Return True if the current block should not get the styles.
   */
  public function exclude($plugin_id, array $plugin_definition) {
    $list = [];

    if (isset($plugin_definition['exclude'])) {
      $list = $plugin_definition['exclude'];
    }

    if (!empty($list) && $this->matchPattern($plugin_id, $list)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Only show styles on specific blocks.
   *
   * Determine if configuration should be only included on certain blocks when a
   * block plugin id or block content type is passed from a plugin.
   *
   * @param string $plugin_id
   *   The ID of the block being checked.
   * @param array $plugin_definition
   *   A list of definitions for a block_style_plugins which could have the key
   *   'include' set as a list of block plugin ids to allow.
   *
   * @return bool
   *   Return True if the current block should only get the styles.
   */
  public function includeOnly($plugin_id, array $plugin_definition) {
    $list = [];

    if (isset($plugin_definition['include'])) {
      $list = $plugin_definition['include'];
    }

    if (empty($list) || $this->matchPattern($plugin_id, $list)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Match a plugin ID against a list of possible plugin IDs.
   *
   * @param string $plugin_id
   *   The ID of the block being checked.
   * @param array $plugin_list
   *   List of plugin ids or plugin patterns of "plugin_id:*".
   *
   * @return bool
   *   Return TRUE if the plugin ID matches a Plugin ID or pattern in the list.
   */
  protected function matchPattern($plugin_id, array $plugin_list) {
    // First check to see if the id is already directly in the list.
    if (in_array($plugin_id, $plugin_list)) {
      return TRUE;
    }

    // Now check to see if this ID is a derivative on something in the list.
    preg_match('/^([^:]+):?/', $plugin_id, $matches);
    if ($matches && in_array($matches[1] . ':*', $plugin_list)) {
      return TRUE;
    }

    // Match any inline blocks in Layout Builder.
    preg_match('/^inline_block:(.+)/', $plugin_id, $matches);
    if ($matches && in_array($matches[1], $plugin_list)) {
      return TRUE;
    }

    return FALSE;
  }

}
