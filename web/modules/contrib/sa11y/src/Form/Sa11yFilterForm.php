<?php

namespace Drupal\sa11y\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Sa11y report filter form.
 */
class Sa11yFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sa11y_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $report_id = NULL, $filters = NULL) {

    if (!$report_id || !$filters) {
      return [];
    }

    $form['report_id'] = [
      '#type' => 'value',
      '#value' => $report_id,
    ];

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter Violations'),
      '#open' => TRUE,
    ];
    foreach ($filters as $key => $filter) {
      $form['filters']['status'][$key] = [
        '#title' => $filter['title'],
        '#type' => 'select',
        '#multiple' => TRUE,
        '#size' => 8,
        '#options' => $filter['options'],
      ];
      if (!empty($_SESSION['sa11y_report_filter_' . $report_id][$key])) {
        $form['filters']['status'][$key]['#default_value'] = $_SESSION['sa11y_report_filter_' . $report_id][$key];
      }
    }

    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($_SESSION['sa11y_report_filter_' . $report_id])) {
      $form['filters']['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->isValueEmpty('type') && $form_state->isValueEmpty('rule') && $form_state->isValueEmpty('impact')) {
      $form_state->setErrorByName('type', $this->t('You must select something to filter by.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('type')) {
      $_SESSION['sa11y_report_filter_' . $form_state->getValue('report_id')]['type'] = $form_state->getValue('type');
    }
    if ($form_state->hasValue('rule')) {
      $_SESSION['sa11y_report_filter_' . $form_state->getValue('report_id')]['rule'] = $form_state->getValue('rule');
    }
    if ($form_state->hasValue('impact')) {
      $_SESSION['sa11y_report_filter_' . $form_state->getValue('report_id')]['impact'] = $form_state->getValue('impact');
    }
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
    $_SESSION['sa11y_report_filter_' . $form_state->getValue('report_id')] = [];
  }

}
