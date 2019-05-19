<?php

/**
 * Implements hook_drush_command().
 */
function wbm2cm_drush_command() {
  return [
    'wbm2cm-migrate' => [
      'description' => 'Migrates your site from Workbench Moderation to Content Moderation.',
    ],
    'wbm2cm-save' => [
      'description' => 'Saves moderation state data to temporary migration tables.',
    ],
    'wbm2cm-clear' => [
      'description' => 'Deletes moderation state data.',
    ],
    'wbm2cm-restore' => [
      'description' => 'Restores moderation state data from temporary migration tables.',
    ],
  ];
}

function drush_wbm2cm_migrate() {
  drush_wbm2cm_save();
  drush_wbm2cm_clear(FALSE);

  $fields = \Drupal::service('wbm2cm.migration_controller')->getOverriddenFields();
  if ($fields) {
    drush_print('It looks like you have overridden the moderation_state base field. These overrides will be reverted because they are incompatible with Content Moderation. You will also need to delete these from your exported config.');

    /** @var \Drupal\Core\Field\Entity\BaseFieldOverride $field */
    foreach ($fields as $field) {
      $field->delete();
      $message = sprintf('Reverted %s. Delete %s.yml from your exported config.', $field->id(), $field->getConfigDependencyName());
      drush_print($message);
    }
  }

  drush_print('Installing Content Moderation...');
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
  $module_installer = \Drupal::service('module_installer');
  $module_installer->uninstall(['workbench_moderation']);
  $module_installer->install(['content_moderation']);

  drush_wbm2cm_restore();

  drush_print('Yay! You have been migrated to Content Moderation.');
}

function drush_wbm2cm_save() {
  /** @var \Drupal\wbm2cm\MigrationController $controller */
  $controller = \Drupal::service('wbm2cm.migration_controller');

  drush_print('Saving existing moderation states to temporary tables...');
  $messages = $controller->executeStepWithMessages('save');
  array_walk($messages, 'drush_print');
}

function drush_wbm2cm_clear($standalone = TRUE) {
  /** @var \Drupal\wbm2cm\MigrationController $controller */
  $controller = \Drupal::service('wbm2cm.migration_controller');

  drush_print('Removing Workbench Moderation data...');
  $messages = $controller->executeStepWithMessages('clear');
  array_walk($messages, 'drush_print');

  if ($standalone) {
    drush_print('You should now be able to uninstall Workbench Moderation and install Content Moderation.');
  }
}

function drush_wbm2cm_restore() {
  /** @var \Drupal\wbm2cm\MigrationController $controller */
  $controller = \Drupal::service('wbm2cm.migration_controller');

  drush_print('Restoring moderation states from temporary tables...');
  $messages = $controller->executeStepWithMessages('restore');
  array_walk($messages, 'drush_print');
}
