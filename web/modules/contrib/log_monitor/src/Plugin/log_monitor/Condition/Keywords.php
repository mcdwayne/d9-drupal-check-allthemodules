<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 11/2/17
 * Time: 3:26 PM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * @LogMonitorCondition(
 *   id = "keywords",
 *   title = @Translation("Keywords"),
 *   description = @Translation("Look for keywords in log messages.")
 * )
 */
class Keywords extends ConditionPluginBase {


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = [
      '#type' => 'textarea',
      '#title' => t('Keywords'),
      '#description' => t('Enter one keyword per line.'),
      '#required' => TRUE,
    ];
    if(isset($this->getConfiguration()['settings']['keywords'])) {
      $form['keywords']['#default_value'] = $this->getConfiguration()['settings']['keywords'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function queryCondition($query) {
    $lines = preg_split("/\\r\\n|\\r|\\n/", $this->getConfiguration()['settings']['keywords']);
    $keywords = array_map('trim', array_filter($lines));
    $group = $query->orConditionGroup();
    foreach ($keywords as $keyword) {
      $group->condition('variables', '%' . $keyword . '%', 'LIKE');
      $group->condition('message', '%' . $keyword . '%', 'LIKE');
    }
    $query->condition($group);
  }

}
