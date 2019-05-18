<?php

namespace Drupal\migrate_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\migrate_report\MigrateReportHelper;

/**
 * Form controller for migrate report.
 */
class MigrateReport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_report.config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('migrate_report.config');

    $can_generate = MigrateReportHelper::canGenerate();
    if (is_array($can_generate)) {
      drupal_set_message([
        '#theme' => 'item_list',
        '#items' => $can_generate,
        '#title' => $this->t('The report cannot be generated due to following reasons:'),
      ], 'warning');
    }

    $form['container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Generate report'),
      '#description' => $this->t('A text report will be generated in the %dir directory. Note that the report is based on the last migration run.', ['%dir' => $config->get('report_dir')]),
    ];
    $form['container']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#disabled' => $can_generate !== TRUE,
    ];

    $report_dir = $config->get('report_dir');
    $files = [];
    foreach (file_scan_directory($report_dir, '/\.txt$/', ['key' => 'name']) as $key => $file) {
      if (file_valid_uri($file->uri)) {
        $files[$key] = Link::fromTextAndUrl($file->filename, Url::fromUri(file_create_url($file->uri)));
      }
      else {
        $files[$key] = $file->filename;
      }
    }
    krsort($files);

    $form['report'] = [
      '#theme' => 'item_list',
      '#items' => $files,
      '#empty' => $this->t('No reports yet. Generate one.'),
      '#title' => $this->t('Reports in %dir:', ['%dir' => $report_dir]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($file = MigrateReportHelper::generate()) {
      drupal_set_message($this->t("Generated report: %report.", ['%report' => $file]));
    }
    else {
      drupal_set_message($this->t("Error generating report."), 'error');
    }
  }

}
