<?php

/**
 * @file
 * Definition of time_spent_all_handler_field_timespent.
 */

namespace Drupal\time_spent_all\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * A handler to provide proper displays for time spent.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("time_spent_all")
 */
class Timespentall extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['time_spent_all']['contains']['granularity'] = array('default' => 5);
    $options['time_spent_all']['contains']['time_spent_all_type'] = array('default' => 'time_spent_all_sec2hms');
    
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['time_spent_all'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Time Spent All'),
    );

    $form['time_spent_all']['time_spent_all_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Display type'),
      '#options' => array(
        'time_spent_all_sec2hms' => t('Sec to HMS (Hours Minutes Seconds)'),
        'granularity' => t('Granularity'),
      ),
      '#default_value' => $this->options['time_spent_all']['time_spent_all_type'],
    );

    $form['time_spent_all']['granularity'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Granularity'),
      '#description' => $this->t('How many different units to display in the string.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[time_spent_all][time_spent_all_type]"]' => array('value' => 'granularity'),
        ),
      ),
      '#default_value' => $this->options['time_spent_all']['granularity'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    if ($this->options['time_spent_all']['time_spent_all_type'] == 'granularity') {
      $granularity = isset($this->options['time_spent_all']['granularity']) ? $this->options['time_spent_all']['granularity'] : 5;
      $time_spent_all = \Drupal::service('date.formatter')->formatInterval($value, $granularity);
    }
    else {
      $time_spent_all = time_spent_all_sec2hms($value);
    }
    return $time_spent_all;
  }

}
