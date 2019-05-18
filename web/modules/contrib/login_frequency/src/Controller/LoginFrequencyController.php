<?php

namespace Drupal\login_frequency\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller routines for Cypress login tracker routes.
 */
class LoginFrequencyController extends ControllerBase {

  /**
   * Displays a report of user logins.
   *
   * @return array
   *   A render array.
   */
  public function report() {
    $header = array(
      array('data' => t('Username'), 'field' => 'ufd.name'),
      array('data' => t('E-mail Id'), 'field' => 'ufd.mail'),
      array('data' => t('Frequency'), 'field' => 'frequency', 'sort' => 'desc'),
    );

    $query = \Drupal::database()->select('login_frequency', 'lf')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->join('users', 'u', 'lf.uid = u.uid');
    $query->join('users_field_data', 'ufd', 'u.uid = ufd.uid');
    $query->addExpression('count(lf.uid)', 'frequency');
    $query->groupBy('lf.uid, name, mail');

    $result = $query
      ->fields('lf', array('uid'))
      ->fields('ufd', array('name', 'mail'))
      ->orderByHeader($header)
      ->limit(50)
      ->execute()
      ->fetchAll();

    return $this->generateReportTable($result, $header);
  }

  /**
   * Renders login histories as a table.
   *
   * @param array $history
   *   A list of login history objects to output.
   * @param array $header
   *   An array containing table header data.
   *
   * @return array
   *   A table render array.
   */
  function generateReportTable(array $history, array $header) {

    $rows = array();
    foreach ($history as $entry) {
      $url = Url::fromRoute('login_frequency.login_detailed_report', ['uid' => $entry->uid]);
      $rows[] = array(
        Link::fromTextAndUrl($entry->name, $url),
        $entry->mail,
        $entry->frequency,
      );
    }
    $output['history'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No login history available.'),
    );
    $output['pager'] = array(
      '#type' => 'pager',
    );

    return $output;
  }

  /**
   * Checks access for the user login report.
   *
   * @return array
   *   A render array.
   */
  public function checkUserReportAccess() {
    $user_roles = \Drupal::currentUser()->getRoles();
    return AccessResult::allowedIf(in_array('administrator', $user_roles));
  }

  /**
   * Displays Detailed reports of users.
   */
  public function content() {
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $uid = $path_args[3];
    $header = array(
      array('data' => t('IP-Address'), 'field' => 'lf.ip_address'),
      array('data' => t('Date'), 'field' => 'lf.login_timestamp', 'sort' => 'desc'),
    );

    $query = \Drupal::database()->select('login_frequency', 'lf')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

      $query->fields('lf', ['ip_address', 'login_timestamp']);
      $query->condition('lf.uid',$uid);
      $query->orderByHeader($header);
      $query->limit(50);
      $results = $query->execute()->fetchAll();

    return $this->generateDetailedReportTable($results, $header);
  }

  /**
   * Renders detailed login histories as a table.
   *
   * @param array $detailhistory
   *   A list of login history objects to output.
   * @param array $header
   *   An array containing table header data.
   *
   * @return array
   *   A table render array.
   */
  function generateDetailedReportTable(array $detailhistory, array $header) {

    $rows = array();
    foreach ($detailhistory as $entry) {
      $date = $entry->login_timestamp;
      $entry_date = date('m/d/Y H:i:s' , $date);
      $rows[] = array(
        $entry->ip_address,
        $entry_date,
      );
    }
    $output['history'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No login history available.'),
    );
    $output['pager'] = array(
      '#type' => 'pager',
    );

    return $output;
  }
}
