<?php

/**
 * @file
 * Contains \Drupal\logman\Form\LogmanStatisticsForm.
 */

namespace Drupal\logman\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\logman\Helper\LogmanWatchdogSearch;
use Drupal\logman\Helper\LogmanApacheSearch;

class LogmanStatisticsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'logman_statistics_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Add the required css and js.
    $form['#attached']['library'][] = 'logman/logman-statistics';

    // JS settings for statistics and charting.
    $settings = [
      'logmanStatistics' => [
        'watchdogPlaceholder' => 'watchdog_chart',
        'apachePlaceholder' => 'apache_chart',
        'watchdogTablePlaceholder' => 'watchdog_table',
        'apacheTablePlaceholder' => 'apache_table',
        'watchdogDataSelector' => 'watchdog_data',
        'apacheDataSelector' => 'apache_data',
      ],
    ];

    $watchdog_against_options = ['severity', 'type'];
    $watchdog = new LogmanWatchdogSearch();
    foreach ($watchdog_against_options as $against) {
      $watchdog_statistics_raw = $watchdog->getStatistics(NULL, $against);
      $settings['logmanStatistics'][$against] = logman_prepare_chartable_data($watchdog_statistics_raw, 'watchdog', $against);
    }

    // Get apache statistics.
    $apache = new LogmanApacheSearch(\Drupal::config('logman.settings')->get('logman_apache_log_path'));
    if ($apache->checkApacheLogPath() === TRUE) {
      $apache_against_options = ['code', 'method'];
      foreach ($apache_against_options as $against) {
        $apache_statistics = $apache->getStatistics(NULL, $against);
        $settings['logmanStatistics'][$against] = logman_prepare_chartable_data($apache_statistics, 'apache', $against);
      }
    }
    else {
      $url = \Drupal\Core\Url::fromRoute('logman.settings_form');
      $link = \Drupal\Core\Link::fromTextAndUrl(t('Please provide a valid apache access log path.'), $url);
      $link = $link->toRenderable();
      drupal_set_message(t('Apache access log path either empty or not valid. %path', array('%path' => render($link))));
    }

    // Add the JS settings array.
    $form['#attached']['drupalSettings'] = $settings;

    $form['statistics'] = [
      '#type' => 'fieldset',
      '#title' => t('Logman Statistics'),
    ];

    $form['statistics']['watchdog'] = [
      '#type' => 'fieldset',
      '#title' => t('Watchdog'),
      '#tree' => TRUE,
      '#suffix' => '<div class="logman_clear"></div>',
    ];

    $form['statistics']['watchdog']['against'] = [
      '#type' => 'select',
      '#options' => [
        'severity' => t('Severity'),
        'type' => t('Type'),
      ],
      '#default_value' => 'severity',
    ];

    // Watchdog chart and data table.
    $form['statistics']['watchdog']['chart'] = [
      '#markup' => '<br /><div id="watchdog_chart"></div>'
      ];

    $form['statistics']['watchdog']['table'] = [
      '#markup' => '<div id="watchdog_table"></div>',
      '#prefix' => '<div class="log_data_table"><b><u>' . t('Chart Data') . '</u></b>',
      '#suffix' => '</div>',
    ];

    $form['statistics']['apache'] = [
      '#type' => 'fieldset',
      '#title' => t('Apache'),
      '#tree' => TRUE,
      '#suffix' => '<div class="logman_clear"></div>',
    ];

    // Display chart only if apache access log path is correctly set.
    if ($apache->checkApacheLogPath() === TRUE) {
      $form['statistics']['apache']['against'] = [
        '#type' => 'select',
        '#options' => [
          'code' => t('Response Code'),
          'method' => t('Method'),
        ],
        '#default_value' => 'code',
      ];

      // Apache chart and data table.
      $form['statistics']['apache']['chart'] = [
        '#markup' => '<div id="apache_chart"></div>'
        ];

      $form['statistics']['apache']['table'] = [
        '#markup' => '<div id="apache_table"></div>',
        '#prefix' => '<div class="log_data_table"><b><u>' . t('Chart Data') . '</u></b>',
        '#suffix' => '</div>',
      ];
    }
    else {
      $form['statistics']['apache']['path_error'] = [
        '#markup' => t('Apache access log path is wrong or not set.')
        ];
    }

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }
}
