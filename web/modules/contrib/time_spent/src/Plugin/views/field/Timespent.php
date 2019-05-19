<?php

/**
 * @file
 * Definition of time_spent_handler_field_timespent.
 */

namespace Drupal\time_spent\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;

/**
 * A handler to provide proper displays for time spent.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("time_spent")
 */
class Timespent extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['time_spent']['contains']['granularity'] = array('default' => 5);
    $options['time_spent']['contains']['time_spent_type'] = array('default' => 'time_spent_sec2hms');
    
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['time_spent'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Time Spent'),
    );

    $form['time_spent']['time_spent_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Display type'),
      '#options' => array(
        'time_spent_sec2hms' => t('Sec to HMS (Hours Minutes Seconds)'),
        'granularity' => t('Granularity'),
      ),
      '#default_value' => $this->options['time_spent']['time_spent_type'],
    );

    $form['time_spent']['granularity'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Granularity'),
      '#description' => $this->t('How many different units to display in the string.'),
      '#states' => array(
        'visible' => array(
          ':input[name="options[time_spent][time_spent_type]"]' => array('value' => 'granularity'),
        ),
      ),
      '#default_value' => $this->options['time_spent']['granularity'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    if ($this->options['time_spent']['time_spent_type'] == 'granularity') {
      $granularity = isset($this->options['time_spent']['granularity']) ? $this->options['time_spent']['granularity'] : 5;
      $time_spent = \Drupal::service('date.formatter')->formatInterval($value, $granularity);
    }
    else {
      $time_spent = time_spent_sec2hms($value);
    }
    return $time_spent;
  }

}
