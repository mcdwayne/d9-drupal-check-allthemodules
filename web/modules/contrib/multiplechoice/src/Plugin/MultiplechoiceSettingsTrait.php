<?php

namespace Drupal\multiplechoice\Plugin;

/**
 * Provides common functionality for the multiplechoice field
 */
trait MultiplechoiceSettingsTrait {

  public function multipleChoiceSettingsForm($settings) {

dpm($settings);
    $attempts = array();
    for ($i=1; $i<50; $i++) {
      $attempts[$i] = $i;
    }
    $element['takes'] = array(
      '#type' => 'select',
      '#title' => t('Attempts Allowed'),
      '#options' => $attempts,
      '#default_value' => isset($settings['takes']) ? $settings['takes'] : 5,
      '#required' => TRUE,
      '#description' => t('The number of attempts a single candidate is allowed to make.'),
    );

    $time = time();
    if (isset($settings['quiz_open'])) {
      $quiz_open = $this->getDateFromTimestamp($settings['quiz_open']);
    }
    else {
      $quiz_open = $this->getDateFromTimestamp($time);
    }
    if (isset($settings['quiz_close'])) {
      $quiz_close = $this->getDateFromTimestamp($settings['quiz_close']);
    }
    else {
      $quiz_close = $this->getDateFromTimestamp($time);
    }



    $element['quiz_open'] = array(
      '#type' => 'date',
      '#title' => t('Opens'),
      '#default_value' => $quiz_open,
      '#required' => TRUE,
      '#description' => t('The date this quiz is open.'),
    );

    $element['quiz_close'] = array(
      '#type' => 'date',
      '#title' => t('Closes'),
      '#default_value' => $quiz_close,
      '#required' => TRUE,
      '#description' => t('The date this quiz closes and can no longer be taken.'),
    );

    $element['pass_rate'] = array(
      '#type' => 'textfield',
      '#title' => t('Pass Rate'),
      '#default_value' => isset($settings['pass_rate']) ? $settings['pass_rate'] : 75,
      '#required' => TRUE,
      '#description' => t('Enter a value between 0 and 100 that represents the percentage correct answers required to
      pass.'),
      '#element_validate' => array('element_validate_integer_positive'),
    );

    $element['backwards_navigation'] = array(
      '#type' => 'checkbox',
      '#title' => t('Backwards Navigation'),
      '#default_value' => isset($settings['backwards_navigation']) ? $settings['backwards_navigation'] : 0,
      '#description' => t('Check this box to allow candidates to navigate backwards.'),
    );

    return $element;
  }

  protected function getDateFromTimestamp($timestamp) {
    return date('Y', $timestamp) . '-' . date('m', $timestamp) . '-' . date('d', $timestamp);
  }
}
