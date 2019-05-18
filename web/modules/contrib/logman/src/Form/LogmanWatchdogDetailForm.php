<?php

/**
 * @file
 * Contains \Drupal\logman\Form\LogmanWatchdogDetailForm.
 */

namespace Drupal\logman\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\logman\Helper\LogmanWatchdogSearch;

class LogmanWatchdogDetailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'logman_watchdog_detail_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Add the required css and js.
    $form['#attached']['library'][] = 'logman/logman-report';

    $log_id = $_GET['wid'];
    $watchdog_log = new LogmanWatchdogSearch();
    $log_detail = $watchdog_log->getLogDetail($log_id);

    $form['log_detail'] = [
      '#type' => 'fieldset',
      '#title' => t('Watchdog Log Detail'),
    ];

    foreach ($log_detail as $field => $value) {
      if ($field == 'message' && !empty($log_detail['variables'])) {
        $replacements = unserialize($log_detail['variables']);
        $field_value = str_replace(array_keys($replacements), array_values($replacements), $value);
      }
      elseif ($field == 'variables' && !empty($value)) {
        $field_value = print_r(unserialize($value), TRUE);
      }
      elseif ($field == 'severity') {
        $field_value = logman_get_severity_name($value);
      }
      elseif ($field == 'timestamp') {
        $field_value = date('Y-m-d H:i:s', $value);
      }
      else {
        $field_value = $value;
      }
      $form['log_detail'][$field] = [
        '#markup' => '<div class="log_field">' . ucwords($field) . ': </div><div class="log_field_value">' . $field_value . '</div>',
        '#prefix' => '<div class = "log_detail_item">',
        '#suffix' => '<div class = "logman_clear"></div></div>',
      ];
    }

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }
}
