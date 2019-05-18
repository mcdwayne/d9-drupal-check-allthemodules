<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/10/17
 * Time: 9:12 AM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * @LogMonitorCondition(
 *   id = "type",
 *   title = @Translation("Type"),
 *   description = @Translation("Module names."),
 * )
 */
class Type extends ConditionPluginBase {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
//    $config = \Drupal::config('log_monitor.settings');
    //@todo: Store possible types allowing users to choose from existing ones.
    $form['types'] = [
      '#type'          => 'textarea',
      '#title'         => t('Type(s)'),
      '#description'   => t('Enter one type per line.'),
      '#required' => TRUE
    ];
    if(isset($this->getConfiguration()['settings']['types'])) {
      $form['types']['#default_value'] = $this->getConfiguration()['settings']['types'];
    }
    return $form;
  }

  public function queryCondition($query) {
    $lines = preg_split("/\\r\\n|\\r|\\n/", $this->getConfiguration()['settings']['types']);
    $types = array_map('trim', array_filter($lines));
    $query->condition('type', $types, 'IN');
  }
}
