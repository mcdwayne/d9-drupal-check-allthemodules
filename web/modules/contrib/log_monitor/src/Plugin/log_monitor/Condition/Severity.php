<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/9/17
 * Time: 3:18 PM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Condition;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Checkboxes;

/**
 * @LogMonitorCondition(
 *   id = "severity",
 *   title = @Translation("Severity"),
 *   description = @Translation("Severity levels."),
 * )
 */
class Severity extends ConditionPluginBase {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['severity_level'] = [
      '#type'          => 'checkboxes',
      '#title'         => t('Severity'),
      '#description'   => t('Select the severity levels you would like to be notified of.'),
      '#required' => TRUE,
      '#options'       => [
        0 => 'Emergency',
        1 => 'Alert',
        2 => 'Critical',
        3 => 'Error',
        4 => 'Warning',
        5 => 'Notice',
        6 => 'Info',
        7 => 'Debug',
      ],
    ];
    if(isset($this->getConfiguration()['settings']['severity_level'])) {
      $form['severity_level']['#default_value'] = $this->getConfiguration()['settings']['severity_level'];
    }
    return $form;
  }

  public function queryCondition($query) {
    $query->condition('severity', Checkboxes::getCheckedCheckboxes($this->getConfiguration()['settings']['severity_level']), 'IN');
  }
}
