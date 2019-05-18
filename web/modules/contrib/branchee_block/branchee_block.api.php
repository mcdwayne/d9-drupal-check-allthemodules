<?php

/**
 * @file
 * Defined hooks for the branchee_block module.
 */

/**
 * Alter Pathauto-generated aliases before saving.
 *
 * @param array $menu_tree
 *   The menu tree that can be altered
 * @param array $context
 *   An associative array of additional options, with the following elements:
 *   - 'module': The module or entity type being aliased.
 *   - 'op': A string with the operation being performed on the object being
 *     aliased. Can be either 'insert', 'update', 'return', or 'bulkupdate'.
 *   - 'source': A string of the source path for the alias (e.g. 'node/1').
 *     This can be altered by reference.
 *   - 'data': An array of keyed objects to pass to token_replace().
 *   - 'type': The sub-type or bundle of the object being aliased.
 *   - 'language': A string of the language code for the alias (e.g. 'en').
 *     This can be altered by reference.
 *   - 'pattern': A string of the pattern used for aliasing the object.
 */
function hook_branchee_block_menu_data_alter(array &$menu_tree, array &$context) {
}
