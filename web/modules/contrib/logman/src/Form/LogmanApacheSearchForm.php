<?php

/**
 * @file
 * Contains \Drupal\logman\Form\LogmanApacheSearchForm.
 */

namespace Drupal\logman\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\logman\Helper\LogmanApacheSearch;

class LogmanApacheSearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'logman_apache_search_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Add the required css and js.
    $form['#attached']['library'][] = 'logman/logman-report';

    // Build form_state values from $_GET.
    // Not ideal but drupal pagination works with query string.
    $field_keys = [
      'http_method',
      'http_response_code',
      'ip',
      'url',
      'date_from',
      'date_to',
    ];
    logman_prepare_form_state($field_keys, $form_state);

    $option = [
      'method' => $form_state->getValue(['http_method']) ? $form_state->getValue([
        'http_method'
        ]) : 'GET',
      'code' => $form_state->getValue(['http_response_code']) ? $form_state->getValue([
        'http_response_code'
        ]) : 200,
      'ip' => $form_state->getValue(['ip']) ? trim($form_state->getValue([
        'ip'
        ])) : '',
      'url' => $form_state->getValue(['url']) ? trim($form_state->getValue([
        'url'
        ])) : '',
      'date_from' => $form_state->getValue(['date_from']) ? $form_state->getValue([
        'date_from'
        ]) : NULL,
      'date_to' => $form_state->getValue(['date_to']) ? $form_state->getValue([
        'date_to'
        ]) : NULL,
    ];

    // Field set container for search form.
    $form['apache_search'] = [
      '#type' => 'fieldset',
      '#title' => t('Apache Search'),
      '#prefix' => '<div class="form_container">',
      '#suffix' => '</div><div class="logman_clear"></div>',
    ];

    $form['apache_search']['ip'] = [
      '#type' => 'textfield',
      '#title' => t('IP'),
      '#size' => 20,
      '#default_value' => (isset($option['ip'])) ? $option['ip'] : '',
      '#prefix' => '<div>',
    ];

    $form['apache_search']['url'] = [
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#size' => 50,
      '#default_value' => (isset($option['url'])) ? $option['url'] : '',
    ];

    $form['apache_search']['http_method'] = [
      '#type' => 'select',
      '#options' => [
        'GET' => t('GET'),
        'POST' => t('POST'),
        'PUT' => t('PUT'),
        'DELETE' => t('DELETE'),
      ],
      '#default_value' => $option['method'],
      '#title' => t('HTTP method'),
    ];

    $form['apache_search']['http_response_code'] = [
      '#type' => 'select',
      '#options' => [
        '100' => t('100'),
        '200' => t('200'),
        '301' => t('301'),
        '302' => t('302'),
        '404' => t('404'),
      ],
      '#default_value' => $option['code'],
      '#title' => t('HTTP response'),
      '#suffix' => '</div><div class="logman_clear"></div>',
    ];

    $form['apache_search']['date_from'] = [
      '#type' => 'date',
      '#title' => t('From'),
      '#default_value' => $option['date_from'],
      '#prefix' => '<div><div class="date_range">',
      '#suffix' => '</div>',
    ];

    $form['apache_search']['date_to'] = [
      '#type' => 'date',
      '#title' => t('To'),
      '#default_value' => $option['date_to'],
      '#prefix' => '<div class="date_range">',
      '#suffix' => '</div>',
    ];

    $form['apache_search']['submit'] = [
      '#id' => 'submit_full_apache',
      '#type' => 'submit',
      '#value' => t('Search'),
    ];

    // Search result.
    $result = $this->searchResult($option);
    // Field set container for search result.
    $form['apache_search_result'] = [
      '#type' => 'fieldset',
      '#title' => t('Apache Search Result'),
      '#prefix' => '<div class="result_container">',
      '#sufix' => '</div>',
    ];
    $form['apache_search_result']['result_count'] = [
      '#markup' => '<div>' . t('Total result:') . ' <strong>' . $result['data_count'] . '</strong></div>'
      ];
    $form['apache_search_result']['result'] = ['#markup' => $result['data']];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    unset($_GET['page']);
    $form_state->setRebuild(TRUE);
  }
    
  protected function searchResult($option, $quantity = 9) {
    $apache_access_log_path = \Drupal::config('logman.settings')->get('logman_apache_log_path');
    $item_per_page = \Drupal::config('logman.settings')->get('logman_log_item_per_page');

    $apache_log = new LogmanApacheSearch($apache_access_log_path, $item_per_page);

    if ($apache_log->checkApacheLogPath() === FALSE) {
      $url = Url::fromRoute('logman.settings_form');
      $link = Link::fromTextAndUrl(t('Please provide a valid apache access log path.'), $url);
      drupal_set_message(t('Apache access log path either empty or not valid. !path', array('!path' => $link)));

      return array(
        'data' => '',
        'data_count' => 0,
      );
    }

    // Set the apache access log read limit.
    $apache_log->setReadLimit(\Drupal::config('logman.settings')->get('logman_apache_read_limit'));

    $search_result = $apache_log->searchLog($option);
    $header = array('IP', 'Time', 'Method', 'URL', 'Response code', 'Agent');
    if ($search_result->totalCount > 0) {
      $table = array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $search_result->data,
        '#attributes' => array(
          'id' => 'logman-apache-search',
        ),
      );
      $output = \Drupal::service('renderer')->render($table);
      pager_default_initialize($search_result->totalCount, $item_per_page);
      $pager = array('#type' => 'pager', array('quantity' => $quantity, 'parameters' => $option));
      $output .= \Drupal::service('renderer')->render($pager);

      return array(
        'data' => $output,
        'data_count' => $search_result->totalCount,
      );
    }
    else {
      if ($search_result->data === FALSE) {
        drupal_set_message(t("Can't open apache access log file at path %logpath"), array('%logpath' => $apache_access_log_path), 'error');
      }
      return array(
        'data' => '',
        'data_count' => 0,
      );
    }
  }
}
