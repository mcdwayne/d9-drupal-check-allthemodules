<?php

namespace Drupal\bcubed_cookie\Plugin\bcubed\Condition;

use Drupal\bcubed\ConditionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides basic condition to check cookies.
 *
 * @Condition(
 *   id = "check_cookie",
 *   label = @Translation("Check Cookie"),
 *   description = @Translation("Flexible condition for evaluating cookies."),
 *   instances = true,
 *   settings = {
 *     "cookiename" = "",
 *     "operator" = "equals",
 *     "cookievalue" = "",
 *     "notfoundbehavior" = 0
 *   }
 * )
 */
class CheckCookie extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_cookie/checkcookie';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['cookiename'] = [
      '#type' => 'textfield',
      '#title' => 'Cookie',
      '#description' => 'Name of cookie to check',
      '#default_value' => $this->settings['cookiename'],
      '#required' => TRUE,
    ];

    $form['operator'] = [
      '#type' => 'select',
      '#title' => 'Comparison',
      '#options' => [
        'equals' => 'Equals',
        'notequal' => 'Not Equal To',
        'greaterthan'  => 'Greater than',
        'lessthan'  => 'Less than',
      ],
      '#default_value' => $this->settings['operator'],
      '#required' => TRUE,
    ];

    $form['cookievalue'] = [
      '#type' => 'textfield',
      '#title' => 'Value',
      '#default_value' => $this->settings['cookievalue'],
      '#required' => TRUE,
    ];

    $form['notfoundbehavior'] = [
      '#type' => 'radios',
      '#title' => 'If the cookie does not exist',
      '#options' => [
        0 => 'Skip this condition',
        1 => 'Fail this condition',
      ],
      '#default_value' => $this->settings['notfoundbehavior'],
      '#required' => TRUE,
    ];

    return $form;
  }

}
