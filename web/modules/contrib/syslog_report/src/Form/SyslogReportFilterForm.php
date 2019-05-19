<?php

/**
 * @file
 * Contains \Drupal\syslog_report\Form\SyslogReportFilterForm.
 */

namespace Drupal\syslog_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SyslogReportFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'syslog_report_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filter_text'] = [
      '#type' => 'textfield',
      '#title' => t('Search word:'),
      '#description' => t('Search word is case-sensitive'),
      '#required' => TRUE,
    ];
    if (!empty($_SESSION['syslog_report_filter'])) {
      $form['filter_text']['#default_value'] = $_SESSION['syslog_report_filter'];
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#button_type' => 'primary',
    );
    if (!empty($_SESSION['syslog_report_filter'])) {
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::resetForm'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('filter_text')) {
      $form_state->setErrorByName('filter_text', $this->t('You must enter something to filter by.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['syslog_report_filter'] = $form_state->getValues()['filter_text'];
  }

  /**
   * Resets the filter form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['syslog_report_filter'] = [];
  }

}