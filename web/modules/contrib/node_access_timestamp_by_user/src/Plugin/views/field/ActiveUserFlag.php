<?php

/**
 * @file
 * Definition of Drupal\node_access_timestamp_by_user\Plugin\views\field\ActiveUserFlag
 */

namespace Drupal\node_access_timestamp_by_user\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Field handler to flag the User Active Status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("active_user_flag")
 */
class ActiveUserFlag extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['type'] = [
      'default' => 'true-false',
    ];

    // Set timeOffest options default.
    $options['time_offset'] = [
      'default' => 5,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $default_formats = [
      'true-false' => [
        t('True'),
        $this
          ->t('False'),
      ],
    ];
    $output_formats = isset($this->definition['output formats']) ? $this->definition['output formats'] : [];
    
    $this->formats = array_merge($default_formats, $output_formats);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    foreach ($this->formats as $key => $item) {
      $options[$key] = implode('/', $item);
    }
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Output format'),
      '#options' => $options,
      '#default_value' => $this->options['type'],
    ];

    // Define our time offset options.
    $options['timeOffest'] = [
      '1' => 1,
      '2' => 2,
      '3' => 3,
      '4' => 4,
      '5' => 5,
      '6' => 6,
      '7' => 7,
      '8' => 8,
      '9' => 9,
      '10' => 10,
      '11' => 11,
      '12' => 12,
      '13' => 13,
      '14' => 14,
      '15' => 15,
      '16' => 16,
      '17' => 17,
      '18' => 18,
      '19' => 19,
      '20' => 20,
      '21' => 21,
      '22' => 22,
      '23' => 23,
      '24' => 24,
      '25' => 25,
      '26' => 26,
      '27' => 27,
      '28' => 28,
      '29' => 29,
      '30' => 30,
    ];

    // Form select element.
    $form['time_offset'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Time Offest by Minutes'),
      '#options' => $options['timeOffest'],
      '#description' => $this->t('Define an "active" user by minute time offset from current time.'),
      '#default_value' => $this->options['time_offset'],
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    // The raw timestamp field value.
    $rawTimestamp = $values->timestamp;

    // Get the current timestamp.
    $currentTime = intval(time());

    // Determine if a user is active.
    if ($rawTimestamp > ($currentTime - ($this->options['time_offset'] * 60))) {
      // User is active.
      return $this->formats[$this->options['type']][0];
    }
    else {
      // User is not active.
      return $this->formats[$this->options['type']][1];
    }

  }
}
