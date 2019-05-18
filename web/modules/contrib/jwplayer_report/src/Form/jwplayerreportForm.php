<?php

namespace Drupal\jwplayer_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jwplayer_report\Controller\ListJwplayerReportController;
/**
 * Configure example settings for this site.
 */
class jwplayerreportForm extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'jwplayer_report_form_id';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames()
  {
    return [
    'jwplayer_report.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    
    if(isset($_POST['reset']) == 'Reset') {
      return new RedirectResponse(\Drupal::url('jwplayer_report.report'));
    }
    $form['media_id'] = array(
    '#id' => 'media_id',
    '#title' => 'MediaId(s)',
    '#type' => 'textfield',
    '#description' => "Please enter single/comma-separated MediaId(s). If MediaId(s) is/are not available then the report will contain data for all MediaId's.",
    '#default_value' => isset($_GET['media_id']) ? str_replace('-', ',', $_GET['media_id']) : '',
   );

  if ((isset($_GET['month_val'])) && ($_GET['month_val'] == 6)) {
    $get_report_range = 1;
  }
  elseif ((isset($_GET['month_val'])) && ($_GET['month_val'] == 12)) {
    $get_report_range = 2;
  }
  elseif ((isset($_GET['month_val'])) && ($_GET['month_val'] == 24)) {
    $get_report_range = 3;
  }

  $form['report_range'] = array(
  '#title' => t('Range'),
  '#type' => 'select',
  '#default_value' => isset($get_report_range) ? $get_report_range : $report_range,
  '#description' => 'Select report range',
  '#options' => array(
      0 => '-- Select --',
      1 => 'Past six months',
      2 => 'Past twelve months',
      3 => 'Past twenty four months',
   ),
  );

  $form['save'] = array(
  '#type' => 'submit',
  '#default_value' => t('Download'),
  );

  $form['list'] = array(
  '#type' => 'submit',
  '#default_value' => t('List'),
  );

  $form['reset'] = array(
  '#type' => 'submit',
  '#default_value' => t('Reset'),
  );

  $filter_data = ListJwplayerReportController::list_jwplayerreport($filter_string);

   $form['searchresult_html'] = $filter_data;

   return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $media_id = trim($_POST['media_id']);
    $option = $_POST['op'];
    $report_range = $_POST['report_range'];
    $triggerd_element = $form_state->getTriggeringElement();
    $triggered_element_value = $triggerd_element['#value'];

    if ($report_range == 1) {
    $month_val = 6;
  }
  elseif ($report_range == 2) {
    $month_val = 12;
  }
  elseif ($report_range == 3) {
    $month_val = 24;
  }

  $new_media_id = str_replace(',', '-', $media_id);
    
    if ($triggered_element_value == 'Download' || $triggered_element_value == 'List') {
      $form_state->setRedirect('jwplayer_report.report',
        array(
            'media_id' => $new_media_id,
            'month_val' => $month_val,
            'option' => $option,
            'report_range' => $report_range,
        )
      );
    }
    else {
      $form_state->setRedirect('jwplayer_report.report');
    }  
  }
}



