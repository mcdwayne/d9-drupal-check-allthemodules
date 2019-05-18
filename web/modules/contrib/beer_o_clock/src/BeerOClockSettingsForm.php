<?php

namespace Drupal\beer_o_clock;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure beer_o_clock settings for this site.
 */
class BeerOClockSettingsForm extends ConfigFormBase {

  /**
  * {@inheritdoc}
  */
  public function getFormId() {
    return 'beer_o_clock_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'beer_o_clock.settings',
    ];
  }

  /**
  * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('beer_o_clock.settings');

    // sunday is day zero to co-incide with PHP.net
    $form['beer_o_clock_day'] = [
      '#type' => 'select',
      '#title' => "Day of the week",
      '#description' => "The day on which Beer O'Clock is on",
      '#options' => [
        0 => t('Sunday'),
        1 => t('Monday'),
        2 => t('Tuesday'),
        3 => t('Wednesday'),
        4 => t('Thursday'),
        5 => t('Friday'),
        6 => t('Saturday'),
      ],
      '#default_value' => $config->get('day'),
      '#required' => TRUE,
    ];
    $form['beer_o_clock_hour'] = [
      '#type' => 'number',
      '#title' => t("Hour of the day"),
      '#description' => t("The time that Beer O'Clock starts - in 24 hour time format"),
      '#min' => 0,
      '#max' => 23,
      '#default_value' => $config->get('hour'),
      '#required' => TRUE,
    ];
    $form['beer_o_clock_duration'] = [
      '#type' => 'number',
      '#title' => t("Duration"),
      '#description' => t("How long Beer O'Clock runs for. The timer will not restart until Beer O'Clock ends"),
      '#min' => 1,
      '#max' => 24,
      '#default_value' => $config->get('duration'),
      '#required' => TRUE,
      '#field_suffix' => t('hours'),
    ];
    $form['beer_o_clock_message'] = [
      '#title' => t("Message to display when it is Beer O'Clock"),
      '#type' => 'textarea',
      '#description' => t("You can use most common HTML tags"),
      '#default_value' => $config->get('message'),
    ];
    $form['beer_o_clock_not_message'] = [
      '#title' => t("Message to display when it is not Beer O'Clock"),
      '#type' => 'textarea',
      '#description' => t("You can use most common HTML tags"),
      '#default_value' => $config->get('not_message'),
    ];
    $form['beer_o_clock_display'] = [
      '#type' => 'select',
      '#title' => "Drink display",
      '#description' => "You can alter the appearance of the drink",
      '#options' => [
        'beer' => t('Beer'),
        'cola' => t('Cola'),
      ],
      '#default_value' => $config->get('display'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
  * {@inheritdoc}
  */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('beer_o_clock_message')) > 40) {
      $form_state->setErrorByName('beer_o_clock_message', $this->t('Message too long.'));
    }
    if (is_numeric($form_state->getValue('beer_o_clock_message'))) {
      $form_state->setErrorByName('beer_o_clock_message', $this->t('Message cannot be number only, has to contain at least one character.'));
    }
  }

  /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('beer_o_clock.settings')
      ->set('day', $form_state->getValue('beer_o_clock_day'))
      ->set('hour', $form_state->getValue('beer_o_clock_hour'))
      ->set('duration', $form_state->getValue('beer_o_clock_duration'))
      ->set('message', $form_state->getValue('beer_o_clock_message'))
      ->set('not_message', $form_state->getValue('beer_o_clock_not_message'))
      ->set('display', $form_state->getValue('beer_o_clock_display'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
