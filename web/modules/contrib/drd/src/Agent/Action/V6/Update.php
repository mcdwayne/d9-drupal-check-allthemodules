<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'Update' code.
 */
class Update extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    global $user;
    $user = user_load(1);
    $old_mode = variable_get('site_offline', FALSE);
    variable_set('site_offline', TRUE);
    $batch = array(
      'operations' => array(array('\Drupal\drd\Agent\Action\V6\Update::findRequiredUpdates', array())),
    );
    batch_set($batch);
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    $_GET['token'] = drupal_get_token('update');
    $_REQUEST['op'] = 'Update';
    $_POST['start'] = array();
    include_once './update.php';
    variable_set('site_offline', $old_mode);
    return array();
  }

  /**
   * Internal function called by update.php
   */
  public static function findRequiredUpdates() {
    drupal_get_messages();
    $form = update_script_selection_form();
    $operations = array();
    foreach ($form['start'] as $module => $def) {
      if (isset($def['#default_value'])) {
        $version = $def['#default_value'];
        drupal_set_installed_schema_version($module, $version - 1);
        $updates = drupal_get_schema_versions($module);
        if ($updates) {
          $max_version = max($updates);
          if ($version <= $max_version) {
            foreach ($updates as $update) {
              if ($update >= $version) {
                $operations[] = array('update_do_one', array($module, $update));
                watchdog('DRD Server', 'Updating '. $module .': version '. $update);
                drupal_set_message('Updating '. $module .': version '. $update);
              }
            }
          }
        }
      }
    }
    $operations[] = array('\Drupal\drd\Agent\Action\V6\Update::captureUpdateMessages', array());
    $batch = array(
      'operations' => $operations,
      'title' => 'Updating',
      'init_message' => 'Starting updates',
      'error_message' => 'An unrecoverable error has occurred. You can find the error message below. It is advised to copy it to the clipboard for reference.',
    );
    batch_set($batch);
  }

  /**
   * Internal function called by update.php
   */
  public static function captureUpdateMessages() {
    $batch_set = &_batch_current_set();
    update_finished(TRUE, $batch_set['results'], array(), format_interval($batch_set['elapsed'] / 1000));
    drupal_set_message(update_results_page());
  }

}
