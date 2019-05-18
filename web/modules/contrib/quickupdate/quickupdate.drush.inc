<?php

/**
 * @file
 * Drush integration for the Quick update module.
 */

/**
 * Implements hook_drush_command().
 */
function quickupdate_drush_command() {
  $items['qup-missing-dependencies'] = array(
    'description' => 'Download missing dependency projects.',
    'aliases' => array('qup-md'),
  );
  return $items;
}

/**
 * Command handler. Downloads missing dependency projects.
 */
function drush_quickupdate_qup_missing_dependencies() {
  $dependencies = quickupdate_load_missing_dependencies();
  $count = count($dependencies);

  if ($count > 0) {
    drush_print(dt('-------------------------------------------------------------'));
    drush_print(dt('The following missing dependency !projects will be downloaded:',
      array(
        '!projects' => format_plural($count, 'project', 'projects'),
      )));
    foreach ($dependencies as $short_name => $item) {
      drush_print(dt($short_name));
    }
    drush_print(dt('-------------------------------------------------------------'));

    foreach ($dependencies as $short_name => $item) {
      exec('drush dl ' . $short_name . ' -y');
    }
    drush_print("\n");
  }

  // Reloads to check if there are more missing dependency projects.
  // If so, download them.
  $dependencies = quickupdate_load_missing_dependencies();
  $count = count($dependencies);

  if ($count > 0) {
    drush_quickupdate_qup_missing_dependencies();
  }
  else {
    drush_print(dt('There are no more missing dependency projects.'));
  }
}
