<?php
/**
 * Created by PhpStorm.
 * User: gurwinder
 * Date: 10/10/17
 * Time: 3:56 PM
 */

namespace Drupal\log_monitor\Plugin\log_monitor\Scheduler;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interval log monitor scheduler..
 *
 * @LogMonitorScheduler(
 *   id = "interval",
 *   title = @Translation("Interval"),
 *   description = @Translation("Run actions at specified intervals."),
 * )
 */
class Interval extends SchedulerConfigurablePluginBase {

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['frequency'] = [
      '#title' => 'Frequency',
      '#type' => 'number',
      '#min' => 1,
      '#max' => 1000,
      '#required' => TRUE,
    ];
    if(isset($this->getConfiguration()['settings']['frequency'])) {
      $form['frequency']['#default_value'] = $this->getConfiguration()['settings']['frequency'];
    }
    $form['interval'] = [
      '#title' => 'Interval',
      '#type' => 'select',
      '#options' => [
        'minutes' => 'Minutes',
        'hours' => 'Hours',
        'days' => 'Days',
        'weeks' => 'Weeks',
        'months' => 'Months'
      ]
    ];
    if(isset($this->getConfiguration()['settings']['interval'])) {
      $form['interval']['#default_value'] = $this->getConfiguration()['settings']['interval'];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return [
      'frequency' => 1,
      'interval' => 'days'
    ];
  }

  /**
   * Return the interval at which processing should happen.
   *
   * @return string
   */
  public function getInterval() {
    $interval = '';
    $frequency = $this->getConfiguration()['settings']['frequency'];
    switch ($this->getConfiguration()['settings']['interval']) {
      case 'minutes':
        $interval = 'PT' . $frequency . 'M';
        break;
      case 'hours':
        $interval = 'PT' . $frequency . 'H';
        break;
      case 'days':
        $interval = 'P' . $frequency . 'D';
        break;
      case 'weeks':
        $interval = 'P' . $frequency . 'W';
        break;
      case 'months':
        $interval = 'P' . $frequency . 'M';
        break;
    }
    return $interval;
  }

}
