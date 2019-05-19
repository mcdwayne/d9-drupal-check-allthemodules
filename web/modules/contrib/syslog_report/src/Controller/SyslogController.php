<?php

/**
 * @file
 * Contains \Drupal\syslog_report\Controller\SyslogController.
 */

namespace Drupal\syslog_report\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;

class SyslogController extends ControllerBase {

  public function displayLog() {
    $form = \Drupal::formBuilder()->getForm('Drupal\syslog_report\Form\SyslogReportFilterForm');
    $log['syslog_report_filter_form'] = $form;
    $result = $this->getLog();
    if (!empty($result)) {
      $result = preg_split('/\r\n|\r|\n/', $result);
      $result = array_reverse($result);
      $num = 1;
      $resultArr = array();
      foreach ($result as $rows) {
        if (!empty($rows)) {
          $row = array();
          $td = array();
          $row = explode('|', $rows);
          //timestamp to date
          $td['date'] = date("M j H:i:s", $row[1]);
          //Referer URL
          $td['referer'] = $row[5];
          $td['message'] = $row[8];
          $td['type'] = $row[2];
          $td['ipaddress'] = $row[3];
          $td['uri'] = $row[4];
          $td['uid'] = $row[6];
          $log['logs'][$num] = $td;
          $num++;
        }
      }
    }
    else {
      $log['logs'] = t("No records.");
    }
    return [
      '#theme' => 'syslog_report',
      '#log_report' => $log,
    ];
  }

  public function getLog() {
    $syslog_identity = \Drupal::config('syslog.settings')->get('identity');
    $syslog_file_path = \Drupal::config('syslog_report.settings')->get('syslog_path');
    if(empty($syslog_file_path)) {
      $syslog_file_path = '/var/log/syslog';
    }
    $page_start = 10;
    if (!empty($_SESSION['syslog_report_filter'])) {
      $filter_value = $_SESSION['syslog_report_filter'];
      $shell_cmd = 'grep ' . $syslog_identity . ' ' . $syslog_file_path . ' | grep ' . $filter_value . ' | tail -' . $page_start . ' | head -10';
    }
    else {
      $shell_cmd = 'grep ' . $syslog_identity . ' ' . $syslog_file_path . ' | tail -' . $page_start . ' | head -10';
    }
    $output = shell_exec($shell_cmd);
    return $output;
  }

}