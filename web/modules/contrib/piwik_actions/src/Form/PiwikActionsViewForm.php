<?php

namespace Drupal\piwik_actions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class PiwikActionsViewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piwik_actions_view';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#method'] = 'get';

    $query = \Drupal::request()->query;
    $storage = [
      'action' => $query->get('action'),
      'start_date' => $query->get('start_date'),
      'end_date' => $query->get('end_date'),
    ];

    if (!empty($storage) || $query->get('op') == 'Export') {
      $actions = \Drupal::service('piwik_actions.visits')->getActions($storage);

      if ($actions === FALSE) {
        drupal_set_message($this->t('Problem retrieving data from analytics service'), 'error');
      }
    }

    if ($query->get('op') == 'Export') {
      // Export to csv.
      $this->csvExport($actions);
      exit();
    }

    $action_types = [
      'action' => 'Action',
      'download' => 'Download',
    ];

    $form['action'] = [
      '#type' => 'select',
      '#title' => t('Action'),
      '#default_value' => $storage['action'] ? $storage['action'] : '',
      '#options' => ['' => '- Any -'] + $action_types,
    ];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => t('Start Date'),
      '#default_value' => $storage['start_date'] ? $storage['start_date'] : date('Y-m-d'),
    ];

    $form['end_date'] = [
      '#type' => 'date',
      '#title' => t('End Date'),
      '#default_value' => $storage['end_date'] ? $storage['end_date'] : '',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#default_value' => t('Submit'),
    ];
    $form['actions']['export'] = [
      '#type' => 'submit',
      '#default_value' => t('Export'),
    ];

    if (!empty($storage) || $actions) {
      $form['output'] = [
        '#type' => 'table',
      ];
      $header = [];
      foreach ($actions as $i => $action) {
        foreach ($action as $k => $v) {
          if (!in_array($k, $header)) {
            $header[] = $k;
          }
        }
      }
      foreach ($actions as $i => $action) {
        foreach ($header as $j => $k) {
          $v = isset($action[$k]) ? $action[$k] : '';
          $form['output'][$i][$k] = is_array($v) ? $v : ['#plain_text' => $v];
        }
      }
      $form['output']['#header'] = $header;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   *
   */
  private function csvExport($actions) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=actions.csv');

    // Create a file pointer connected to the output stream.
    $output = fopen('php://output', 'w');

    $header = [];
    foreach ($actions as $i => $action) {
      foreach ($action as $k => $v) {
        if (!in_array($k, $header)) {
          $header[] = $k;
        }
      }
    }
    sort($header);
    fputcsv($output, $header);
    foreach ($actions as $i => $action) {
      $row = [];
      foreach ($header as $k) {
        $v = isset($action[$k]) ? $action[$k] : '';
        if (is_array($v)) {
          // Assume it's a render array; render and convert to text.
          $rendered = \Drupal::service('renderer')->render($v);
          $v = strip_tags($rendered);
        }
        $row[] = $v;
      }
      fputcsv($output, $row);
    }
    fclose($output);
  }

}
