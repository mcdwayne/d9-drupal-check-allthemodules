<?php

/**
 * @file
 * Hooks for the patchinfo module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to change the patchinfo_list_row.
 *
 * Modules can add, remove or change information in the patchinfo_list_row
 * output of the drush patchinfo-list command.
 *
 * @param array $patchinfo_list_row
 *   Array with the information of the patch available for the drush command.
 * @param array $patch
 *   Array with information of the patch from the patch source.
 */
function patchinfo_drupalorg_patchinfo_list_row_alter(array &$patchinfo_list_row, array $patch) {
  // Add extra dummy text to info column.
  $patchinfo_list_row['info'] .= 'dummy text at end of info column';
}

/**
 * @} End of "addtogroup hooks".
 */
