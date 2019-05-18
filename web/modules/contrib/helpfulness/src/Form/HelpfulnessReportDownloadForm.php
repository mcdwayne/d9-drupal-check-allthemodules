<?php

namespace Drupal\helpfulness\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Defines a form for the report download.
 */
class HelpfulnessReportDownloadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'helpfulness_report_download_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Status selection.
    $form['status'] = [
      '#type' => 'select',
      '#title' => t('Download messages of status:'),
      '#options' => [
        '-1' => t('New & Archived'),
        HELPFULNESS_STATUS_OPEN => t('New'),
        HELPFULNESS_STATUS_ARCHIVED => t('Archived'),
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Download'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();
    // Get the status from the dropdown.
    $requested_status = $values['status'];

    // Build the query to retrieve the feedbacks.
    // Build the query.
    $query = Database::getConnection()->select('helpfulness', 'hf');
    $query->fields('hf');
    $query->orderBy('timestamp', 'ASC');

    switch ($requested_status) {
      case HELPFULNESS_STATUS_OPEN:
        $query->condition('status', HELPFULNESS_STATUS_OPEN);
        break;

      case HELPFULNESS_STATUS_ARCHIVED:
        $query->condition('status', HELPFULNESS_STATUS_ARCHIVED);
        break;

      default:
        $status_array[] = HELPFULNESS_STATUS_OPEN;
        $status_array[] = HELPFULNESS_STATUS_ARCHIVED;
        $query->condition('status', $status_array, 'IN');
        break;
    }

    // Header for the output file.
    $csv_output = t('"Status",');
    $csv_output .= t('"User ID",');
    $csv_output .= t('"Helpful",');
    $csv_output .= t('"Message",');
    $csv_output .= t('"Base URL",');
    $csv_output .= t('"System Path",');
    $csv_output .= t('"Path Alias",');
    $csv_output .= t('"Browser Information",');
    $csv_output .= t('"Time"');
    $csv_output .= "\n";

    // Add the data from all requested feedbacks.
    foreach ($query->execute() as $row) {
      // Status.
      switch ($row->status) {
        case HELPFULNESS_STATUS_OPEN:
          $csv_output .= t('"New",');
          break;

        case HELPFULNESS_STATUS_ARCHIVED:
          $csv_output .= t('"Archived",');
          break;

        default:
          $csv_output .= t('"Unknown",');
          break;
      }

      // User Id, and user name for convenience.
      if ($row->uid == 0) {
        $username = t('Anonymous');
      }
      else {
        $tmp_user = \Drupal::entityTypeManager()->getStorage('user')->load($row->uid);
        $username = $tmp_user->getDisplayName();
      }
      $csv_output .= '"' . $row->uid . ' (\'' . $username . '\')",';

      // Helpfulnes Rating.
      if ($row->helpfulness) {
        $csv_output .= t('"Yes",');
      }
      else {
        $csv_output .= t('"No",');
      }

      // Feedback message.
      $message = str_replace('"', '""', $row->message);
      $csv_output .= '"' . $message . '",';

      // Path information.
      $csv_output .= '"' . $row->base_url . '",';
      $csv_output .= '"' . $row->system_path . '",';
      $csv_output .= '"' . $row->path_alias . '",';

      // Browser info.
      $csv_output .= '"' . $row->useragent . '",';

      // Time of submission.
      $csv_output .= '"' . format_date($row->timestamp, 'custom', "Y-m-d H-i-s") . '",';

      // That should be everything for this submission.
      $csv_output .= "\n";
    }// End foreach

    // Build the filename and start the download.
    $prefix = t('feedbacks');
    $filename = $prefix . "_" . format_date(time(), 'custom', "Y-m-d_H-i-s");
    header("Content-type: application/vnd.ms-excel");
    header("Content-disposition: filename=" . $filename . ".csv");
    print $csv_output;

    exit();
  }

}
