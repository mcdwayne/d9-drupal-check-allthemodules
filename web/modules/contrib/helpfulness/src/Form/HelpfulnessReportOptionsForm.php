<?php

namespace Drupal\helpfulness\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for the report options.
 */
class HelpfulnessReportOptionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helpfulness_report_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the configuration.
    $config = $this->config('helpfulness.settings');

    $form['helpfulness_report_option_info'] = [
      '#type' => 'item',
      '#title' => t('Select the columns you would like to have displayed in the report:'),
    ];

    // Options for the display of columns in the feedback report.
    $form['helpfulness_report_option_display_username'] = [
      '#type' => 'checkbox',
      '#title' => t('User'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_username'),
    ];

    $form['helpfulness_report_option_display_helpfulness'] = [
      '#type' => 'checkbox',
      '#title' => t('Helpfulness Rating'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_helpfulness'),
    ];

    $form['helpfulness_report_option_display_message'] = [
      '#type' => 'checkbox',
      '#title' => t('Message'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_message'),
    ];

    $form['helpfulness_report_option_display_base_url'] = [
      '#type' => 'checkbox',
      '#title' => t('Base URL'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_base_url'),
    ];

    $form['helpfulness_report_option_display_system_path'] = [
      '#type' => 'checkbox',
      '#title' => t('System Path'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_system_path'),
    ];

    $form['helpfulness_report_option_display_alias'] = [
      '#type' => 'checkbox',
      '#title' => t('Alias'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_alias'),
    ];

    $form['helpfulness_report_option_display_date'] = [
      '#type' => 'checkbox',
      '#title' => t('Date'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_date'),
    ];

    $form['helpfulness_report_option_display_time'] = [
      '#type' => 'checkbox',
      '#title' => t('Time'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_time'),
    ];

    $form['helpfulness_report_option_display_useragent'] = [
      '#type' => 'checkbox',
      '#title' => t('Browser Info'),
      '#return_value' => 1,
      '#default_value' => $config->get('helpfulness_report_option_display_useragent'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $count = $form_state->getValue('helpfulness_report_option_display_username');
    $count += $form_state->getValue('helpfulness_report_option_display_helpfulness');
    $count += $form_state->getValue('helpfulness_report_option_display_message');
    $count += $form_state->getValue('helpfulness_report_option_display_base_url');
    $count += $form_state->getValue('helpfulness_report_option_display_system_path');
    $count += $form_state->getValue('helpfulness_report_option_display_alias');
    $count += $form_state->getValue('helpfulness_report_option_display_date');
    $count += $form_state->getValue('helpfulness_report_option_display_time');
    $count += $form_state->getValue('helpfulness_report_option_display_useragent');

    if ($count < 2) {
      $form_state->setErrorByName('helpfulness_report_option_info', $this->t('Please select at least two items to display in the report.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the configuration.
    $config = \Drupal::configFactory()->getEditable('helpfulness.settings');

    $config->set('helpfulness_report_option_display_username', $form_state->getValue('helpfulness_report_option_display_username'))
      ->set('helpfulness_report_option_display_helpfulness', $form_state->getValue('helpfulness_report_option_display_helpfulness'))
      ->set('helpfulness_report_option_display_message', $form_state->getValue('helpfulness_report_option_display_message'))
      ->set('helpfulness_report_option_display_base_url', $form_state->getValue('helpfulness_report_option_display_base_url'))
      ->set('helpfulness_report_option_display_system_path', $form_state->getValue('helpfulness_report_option_display_system_path'))
      ->set('helpfulness_report_option_display_alias', $form_state->getValue('helpfulness_report_option_display_alias'))
      ->set('helpfulness_report_option_display_date', $form_state->getValue('helpfulness_report_option_display_date'))
      ->set('helpfulness_report_option_display_time', $form_state->getValue('helpfulness_report_option_display_time'))
      ->set('helpfulness_report_option_display_useragent', $form_state->getValue('helpfulness_report_option_display_useragent'))
      ->save();

    drupal_set_message($this->t('The options have been updated.'));
  }

}
