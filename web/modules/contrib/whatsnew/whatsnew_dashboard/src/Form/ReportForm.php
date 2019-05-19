<?php

namespace Drupal\whatsnew_dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\whatsnew_dashboard\Controller\WhatsnewDashboardController;

/**
 * Implements report form.
 */
class ReportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // The default filter options.
    $default_filter_options = [
      WhatsnewDashboardController::STATUS_OK => FALSE,
      WhatsnewDashboardController::STATUS_MINOR => FALSE,
      WhatsnewDashboardController::STATUS_MAJOR => FALSE,
      WhatsnewDashboardController::STATUS_UNKNOWN => TRUE,
      WhatsnewDashboardController::STATUS_SECURITY => TRUE,
      WhatsnewDashboardController::STATUS_UNSUPPORTED => TRUE,
    ];

    // Maintain a list of the applied filters.
    $applied_filters = [];
    $use_cache = FALSE;

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => 'Status filters',
    ];

    // Create checkboxes for each filter option.
    foreach ($default_filter_options as $key => $default) {

      $input_name = 'setting_' . $key;

      if ($form_state->isSubmitted()) {
        $use_cache = TRUE;
        $value = $form_state->getValue($input_name);
        if ($value) {
          $applied_filters[] = $key;
          $default = TRUE;
        }
        else {
          $default = FALSE;
        }
      }
      else {
        if ($default) {
          $applied_filters[] = $key;
        }
      }

      $form['settings'][$input_name] = [
        '#type' => 'checkbox',
        '#title' => ucfirst($key),
        '#default_value' => $default,
      ];

    }

    $form['settings']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Update',
    ];

    $form['table'] = WhatsnewDashboardController::buildReport($applied_filters, $use_cache);
    $form['table']['#attached']['library'][] = 'whatsnew_dashboard/dashboard-table';
    $form['table']['report']['#prefix'] = '<span class="report_wrapper">';
    $form['table']['report']['#suffix'] = '</span>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

}
