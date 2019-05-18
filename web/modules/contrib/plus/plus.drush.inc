<?php
//
//use Drupal\plus\Plus;
//use Drush\Log\LogLevel;
//
///**
// * Implements drush_hook_post_COMMAND().
// */
//function drush_plus_post_updatedb() {
//  drush_bootstrap_max();
//
//  $updates = [];
//  foreach (Plus::getThemes() as $theme) {
//    foreach ($theme->getPendingUpdates() as $update) {
//      $updates[] = [
//        'theme' => $theme->getName(),
//        'update' => $update->getLabel() . ' (' . $update->getSchema() . ')',
//        'description' => $update->getDescription() ?: dt('N/A'),
//        'object' => $update,
//      ];
//    }
//  }
//
//  if ($updates) {
//    // Display a list of pending theme updates.
//    drush_print_format($updates, 'table', [
//      'pipe-format' => 'csv',
//      'field-labels' => ['theme' => 'Theme', 'update' => 'Update', 'description' => 'Description'],
//      'fields-default' => ['theme', 'update', 'description'],
//      'output-data-type' => 'format-table',
//    ]);
//
//    if (!drush_confirm(dt('Do you wish to run all pending theme updates?'))) {
//      return drush_user_abort();
//    }
//
//    // Process each update.
//    foreach ($updates as $data) {
//      /** @var \Drupal\plus\Plugin\Update\UpdateInterface $update */
//      $update = $data['object'];
//      $context = [];
//      try {
//        $update->process($update->getTheme(), $context);
//      }
//      catch (\Exception $e) {
//
//      }
//    }
//  }
//  else {
//    return drush_log(dt('No theme database updates required'), LogLevel::SUCCESS);
//  }
//
//}
