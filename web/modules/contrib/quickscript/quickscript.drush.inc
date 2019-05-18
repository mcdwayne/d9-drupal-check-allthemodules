<?php
/**
 * @file
 * Contains Quick Script Drush commands.
 */

/**
 * Implements hook_drush_command().
 */
function quickscript_drush_command() {
  $items = [];
  $items['quickscript'] = [
    'description' => 'Run a Quick Script and return the result.',
    'arguments' => [
      'machine_name' => 'Machine name of the Quick Script to run.',
      '$_QS' => 'Key value pairs to pass to the script in the $_QS array. Usage: "val1=something&val2=another_thing"',
    ],
    'aliases' => ['qs'],
  ];
  $items['quickscript-list'] = [
    'description' => 'List all Quick Scripts with their machine name and ID.',
    'aliases' => ['qsl'],
  ];
  return $items;
}

/**
 * Runs a Quick Script.
 */
function drush_quickscript($machine_name, $_QS = '') {
  if (!empty($_QS)) {
    parse_str($_QS, $_GET['qs']);
  }
  $quickscript = quickscript_load($machine_name);
  if (!$quickscript || !$quickscript instanceof \Drupal\quickscript\Entity\QuickScript) {
    return drush_set_error(t('"@machine_name" is not a valid Quick Script.', ['@machine_name' => $machine_name]));
  }
  return $quickscript->execute();
}

/**
 * Lists all Quick Scripts in a table.
 */
function drush_quickscript_list() {
  $scripts = \Drupal\quickscript\Entity\QuickScript::loadAll();
  if (empty($scripts)) {
    return t('There are no Quick Scripts created yet.');
  }
  $rows = [];
  $rows[] = [t('ID'), t('Machine Name'), t('Label')];
  foreach ($scripts as $script) {
    $rows[] = [$script->id(), $script->machine_name->value, $script->label()];
  }
  drush_print_table($rows, TRUE);
}
