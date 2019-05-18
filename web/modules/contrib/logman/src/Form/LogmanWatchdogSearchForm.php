<?php

/**
 * @file
 * Contains \Drupal\logman\Form\LogmanWatchdogSearchForm.
 */

namespace Drupal\logman\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\logman\Helper\LogmanWatchdogSearch;

class LogmanWatchdogSearchForm extends FormBase {

  /**
   * Log message truncate length.
   */
  const MSG_TRUNCATE_LENGTH = 150;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'logman_watchdog_search_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Add the required css and js.
    $form['#attached']['library'][] = 'logman/logman-report';

    // Build form_state values from $_GET.
    // Not ideal but drupal pagination works with query string.
    $field_keys = [
      'search_key',
      'log_type',
      'severity',
      'uid',
      'location',
      'referer',
      'items_per_page',
      'date_from',
      'date_to',
    ];
    logman_prepare_form_state($field_keys, $form_state);

    // @FIXME: D8 doesn't allows this.
    //$form['#action'] = url(current_path());

    // Field set container for search form.
    $form['watchdog_search'] = [
      '#type' => 'fieldset',
      '#title' => t('Watchdog Search'),
      '#prefix' => '<div class="form_container">',
      '#suffix' => '</div><div class="logman_clear"></div>',
    ];
    // Search key.
    $form['watchdog_search']['search_key'] = [
      '#type' => 'textfield',
      '#title' => t('Search Message'),
      '#default_value' => !$form_state->getValue([
        'search_key'
        ]) ? $form_state->getValue(['search_key']) : '',
      '#prefix' => '<div>',
    ];

    // Log type to search.
    $log_type_options = ['all' => t('All')] + LogmanWatchdogSearch::getLogTypes();
    $form['watchdog_search']['log_type'] = [
      '#type' => 'select',
      '#title' => t('Log Type'),
      '#options' => $log_type_options,
      '#default_value' => !$form_state->getValue([
        'log_type'
        ]) ? $form_state->getValue(['log_type']) : 'all',
    ];

    // Log severity.
    $form['watchdog_search']['severity'] = [
      '#type' => 'select',
      '#title' => t('Severity'),
      '#options' => $this->getSeverityLevels(),
      '#default_value' => !$form_state->getValue([
        'severity'
        ]) ? $form_state->getValue(['severity']) : 'all',
    ];

    // User.
    $form['watchdog_search']['uid'] = [
      '#type' => 'textfield',
      '#title' => t('User'),
      '#size' => 18,
      '#default_value' => !$form_state->getValue([
        'uid'
        ]) ? $form_state->getValue(['uid']) : '',
      '#suffix' => '</div><div class="logman_clear"></div>',
    ];

    // Location.
    $form['watchdog_search']['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location'),
      '#default_value' => !$form_state->getValue([
        'location'
        ]) ? $form_state->getValue(['location']) : '',
      '#prefix' => '<div>',
    ];

    // Referrer.
    $form['watchdog_search']['referer'] = [
      '#type' => 'textfield',
      '#title' => t('Referer'),
      '#default_value' => !$form_state->getValue([
        'referer'
        ]) ? $form_state->getValue(['referer']) : '',
      '#suffix' => '</div><div class="logman_clear"></div>',
    ];

    // Date range from.
    $form['watchdog_search']['date_from'] = [
      '#type' => 'date',
      '#title' => t('From'),
      '#default_value' => $form_state->getValue([
        'date_from'
        ]) ? $form_state->getValue(['date_from']) : NULL,
      '#prefix' => '<div><div class="date_range">',
      '#suffix' => '</div>',
    ];

    // Date range to.
    $form['watchdog_search']['date_to'] = [
      '#type' => 'date',
      '#title' => t('To'),
      '#default_value' => $form_state->getValue([
        'date_to'
        ]) ? $form_state->getValue(['date_to']) : NULL,
      '#prefix' => '<div class="date_range">',
      '#suffix' => '</div>',
    ];

    $form['watchdog_search']['submit'] = [
      '#id' => 'submit_full_watchdog',
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    // Display the search result.
    $search_result = $form_state->getValue('search_result');
    if (empty($search_result)) {
      $items_per_page = \Drupal::config('logman.settings')->get('logman_items_per_page');
      $search_result = $this->searchResult($form_state, $items_per_page);
    }
    if (!empty($search_result['themed_result'])) {
      $form['watchdog_search_result'] = [
        '#type' => 'fieldset',
        '#title' => t('Watchdog Search Result'),
        '#prefix' => '<div class="result_container">',
        '#sufix' => '</div>',
      ];

      $form['watchdog_search_result']['srearch_result'] = [
        '#markup' => $search_result['themed_result']
        ];

      $form['watchdog_search_result']['pagination'] = [
        '#markup' => $search_result['result']->pagination
        ];
    }

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    unset($_GET['page']);
    $items_per_page = \Drupal::config('logman.settings')->get('logman_items_per_page');
    $search_result = $this->searchResult($form_state, $items_per_page);
    $form_state->setValue('search_result', $search_result);
    $form_state->setRebuild(TRUE);
  }

  protected function searchResult($form_state, $items_per_page = 10, $quantity = 9) {
    // Check for the log type.
    $search_key = $form_state->getValue('search_key');
    $log_type = $form_state->getValue('log_type');
    if (isset($search_key) || isset($log_type)) {
      $log_type = isset($log_type) ? $log_type : 'all';
      if ($log_type == 'all') {
        $watchdog_log = new LogmanWatchdogSearch($search_key);
      }
      else {
        $watchdog_log = new LogmanWatchdogSearch($search_key, $log_type);
      }
    }
    else {
      $watchdog_log = new LogmanWatchdogSearch();
    }

    // Prepare the params array.
    $params = array();
    $search_fields = array('severity', 'uid', 'location', 'referer');
    foreach ($search_fields as $search_field) {
      $value = $form_state->getValue($search_field);
      if (isset($value) && $value != 'all') {
        $params[$search_field] = $value;
      }
    }
    // Prepare the date range.
    $value_from = $form_state->getValue('date_from');
    $value_to = $form_state->getValue('date_to');
    if (!empty($value) && !empty($value_to)) {
      $params['date_range'] = array(strtotime($value_from), strtotime($value_to));
    }
    elseif (!empty($value_from) && empty($value_to)) {
      $params['date_range'] = array(strtotime($value_from));
    }
    else {
      $params['date_range'] = array();
    }

    $watchdog_log->setLimit($items_per_page);
    $watchdog_log->setQuantity($quantity);
    $search_result = $watchdog_log->searchLog($params);
    if (count($search_result->matches) > 0) {
      // Get the Severity levels.
      $severity_levels = $this->getSeverityLevels();

      $rows = array();
      foreach ($search_result->matches as $data) {
        $replacements = unserialize($data['variables']);
        $message = $data['message'];
        if (!empty($replacements)) {
          $message = str_replace(array_keys($replacements), array_values($replacements), $data['message']);
        }

        $url = Url::fromRoute('logman.watchdog_detail_form', array('wid' => $data['wid']), array(
          'attributes' => array(
            'target' => '_blank',
          ),
        ));
        $message_truncate_length =  \Drupal::config('logman.settings')->get('logman_message_truncate_length');
        $message_truncate_length = empty($message_truncate_length) ? self::MSG_TRUNCATE_LENGTH : $message_truncate_length;
        $display_msg = (strlen($message) > $message_truncate_length) ? substr($message, 0, $message_truncate_length) . '...' : $message;
        $log_detail_link = Link::fromTextAndUrl($display_msg, $url);
        $rows[] = array(
          $data['wid'],
          ucwords($data['type']),
          $log_detail_link,
          ucwords($severity_levels[$data['severity']]),
          $data['uid'],
          $data['location'],
          $data['referer'],
          date('Y-m-d H:i:s', $data['timestamp']),
        );
      }
      $header = array(
        t('Wid'),
        t('Type'),
        t('Message'),
        t('Severity'),
        t('User'),
        t('Location'),
        t('Referrer'),
        t('DateTime'),
      );

      $table = array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#attributes' => array(
          'id' => 'logman-watchdog-search',
        ),
      );
      // var_dump($search_result->pagination);
      $themed_result = \Drupal::service('renderer')->render($table);
    }
    else {
      $themed_result = t('No matches found.');
    }

    return array(
      'result' => $search_result,
      'themed_result' => $themed_result,
    );
  }

  /**
   * Provides severity levels.
   *
   * @return array
   */
  protected function getSeverityLevels() {
    // Severity levels.
    $levels = ['all' => 'All'];
    $severity_levels = drupal_error_levels();
    foreach ($severity_levels as $level) {
      list($name, $level_num) = $level;
      $levels[] = ucwords($name);
    }

    return $levels;
  }

}
