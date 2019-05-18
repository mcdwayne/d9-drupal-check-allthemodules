<?php

namespace Drupal\drd\Agent\Action\V7;

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
    $old_mode = variable_get('maintenance_mode', FALSE);
    variable_set('maintenance_mode', TRUE);
    $batch = array(
      'operations' => array(
        array(
          '\Drupal\drd\Agent\Action\V7\Update::findRequiredUpdates',
          array()
        )
      ),
    );
    batch_set($batch);
    $batch = &batch_get();
    $batch['progressive'] = FALSE;
    $_GET['token'] = drupal_get_token('update');
    $_REQUEST['op'] = 'Apply pending updates';
    $_POST['start'] = array();
    include_once './update.php';
    variable_set('maintenance_mode', $old_mode);
    return array();
  }

  /**
   * Internal function called by update.php
   */
  public static function findRequiredUpdates() {
    drupal_get_messages();
    $form = array();
    $form_state = array();
    $form = update_script_selection_form($form, $form_state);
    $operations = array();
    if (isset($form) && isset($form['start'])) {
      $start = array();
      foreach ($form['start'] as $module => $def) {
        if (isset($def['#value']) && module_exists($module)) {
          $start[$module] = $def['#value'];
        }
      }
      $updates = update_resolve_dependencies($start);
      $dependency_map = array();
      foreach ($updates as $function => $update) {
        $dependency_map[$function] = !empty($update['reverse_paths']) ? array_keys($update['reverse_paths']) : array();
      }
      foreach ($updates as $update) {
        if ($update['allowed']) {
          if (isset($start[$update['module']])) {
            drupal_set_installed_schema_version($update['module'], $update['number'] - 1);
            unset($start[$update['module']]);
          }
          $function = $update['module'] . '_update_' . $update['number'];
          $operations[] = array(
            'update_do_one',
            array(
              $update['module'],
              $update['number'],
              $dependency_map[$function]
            ),
          );
          watchdog('DRD Server', 'Updating ' . $update['module'] . ': version ' . $update['number']);
          drupal_set_message('Updating ' . $update['module'] . ': version ' . $update['number']);
        }
      }
    }
    $operations[] = array('\Drupal\drd\Agent\Action\V7\Update::captureUpdateMessages', array());
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
