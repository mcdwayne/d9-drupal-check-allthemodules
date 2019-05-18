<?php

namespace Drupal\autoban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\autoban\Controller\AutobanController;

/**
 * Analyze watchdog entries for IP addresses for ban.
 */
class AutobanAnalyzeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autoban_analyze_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $rows = [];
    $header = [
      ['data' => $this->t('Count'), 'field' => 'cnt', 'sort' => 'desc'],
      ['data' => $this->t('Type'), 'field' => 'type'],
      ['data' => $this->t('Message'), 'field' => 'message'],
      $this->t('Operations'),
    ];

    // Run analyze query.
    $threshold_analyze = $this->config('autoban.settings')->get('autoban_threshold_analyze') ?: 5;
    $result = $this->getAnalyzeResult($header, $threshold_analyze);
    if (count($result)) {
      $destination = $this->getDestinationArray();
      $url_destination = ['destination' => $destination['destination']];

      foreach ($result as $item) {
        $message = $this->formatMessage($item);
        $message = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
        $row = [$item->cnt, $item->type, $message];

        $links = [];
        $query = [
          'query' => [
            'type' => $item->type,
            'message' => $message,
            $url_destination,
          ],
        ];

        $links['add_rule'] = [
          'title' => $this->t('Add rule'),
          'url' => Url::fromRoute('entity.autoban.add_form', [], $query),
        ];
        $links['test'] = [
          'title' => $this->t('Test'),
          'url' => Url::fromRoute('autoban.test', ['rule' => AUTOBAN_FROM_ANALYZE], $query),
        ];

        $row[] = [
          'data' => [
            '#type' => 'operations',
            '#links' => $links,
          ],
        ];

        $rows[] = $row;
      }
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Rules generate'),
      '#suffix' => '<div>' . $this->t('Automatic creation autoban rules for checked rows.') . '</div>',
    );

    $form['analyze_table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $rows,
      '#empty' => $this->t('No data for ban.'),
      '#weight' => 120,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rules = [];
    foreach ($form_state->getValue('analyze_table') as $key => $value) {
      if ($value != NULL) {
        $rules[$key] = [
          'type' => $form['analyze_table']['#options'][$key][1],
          'message' => $form['analyze_table']['#options'][$key][2],
        ];
      }
    }

    if (!empty($rules)) {

      // Provider.
      $providers = [];
      $controller = new AutobanController();
      $banManagerList = $controller->getBanProvidersList();
      if (!empty($banManagerList)) {
        foreach ($banManagerList as $id => $item) {
          $providers[$id] = $item['name'];
        }
      }
      else {
        drupal_set_message(
          $this->t('List ban providers is empty. You have to enable at least one Autoban providers module.'),
          'warning'
        );
        return;
      }

      $provider = NULL;
      if (count($providers) == 1) {
        $provider = array_keys($providers)[0];
      }
      else {
        $last_provider = $this->config('autoban.settings')->get('autoban_provider_last');
        if ($last_provider && isset($providers[$last_provider])) {
          $provider = $last_provider;
        }
      }
      if (empty($provider)) {
        $provider = array_keys($providers)[0];
      }

      // Threshold
      $threshold = $this->config('autoban.settings')->get('autoban_threshold_analyze') ?: 5;
      $thresholds_config = $this->config('autoban.settings')->get('autoban_thresholds');
      $thresholds = !empty($thresholds_config) ?
        explode("\n", $thresholds_config)
        : [1, 2, 3, 5, 10, 20, 50, 100];

      if (!in_array($threshold, $thresholds)) {
        $threshold = max(array_filter($thresholds, function ($v) use ($threshold) {
          return $v < $threshold;
        }));
      }

      $next = \Drupal::entityQuery('autoban')->count()->execute() + 1;
      foreach ($rules as $key => $value) {
        $value['provider'] = $provider;
        $value['threshold'] = $threshold;
        $value['id'] = "rule_analyze$next";
        $next++;

        $autoban = entity_create('autoban', $value);
        $autoban->save();
      }

      drupal_set_message(t('Created rules: @count', ['@count' => count($rules)]));
    }
    else {
      drupal_set_message(t('No rules for generate'), WARNING);
    }
  }

  /**
   * Get analyze result.
   *
   * @param array $header
   *   Query header.
   * @param int $threshold
   *   Threshold for watchdog entries which added to result.
   *
   * @return array
   *   Watchdog table data as query result.
   */
  private function getAnalyzeResult(array $header, $threshold = 5) {
    $dblog_type_exclude = $this->config('autoban.settings')->get('autoban_dblog_type_exclude') ?: "autoban\ncron\nphp\nsystem\nuser";

    $query = \Drupal::database()->select('watchdog', 'log');
    $query->fields('log', ['message', 'variables', 'type']);
    $query->addExpression('COUNT(*)', 'cnt');
    $query->condition('log.type', explode("\n", $dblog_type_exclude), 'NOT IN');
    $query->groupBy('log.message, log.variables, log.type');
    $query->having('COUNT(*) >= :threshold', [':threshold' => $threshold]);
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header);

    $result = $table_sort->execute()->fetchAll();
    return $result;
  }

  /**
   * Formats a database log message.
   *
   * @param object $row
   *   The record from the watchdog table. The object properties are: wid, uid,
   *   severity, type, timestamp, message, variables, link, name.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|false
   *   The formatted log message
   */
  public function formatMessage($row) {
    // Check for required properties.
    if (isset($row->message, $row->variables)) {
      $variables = @unserialize($row->variables);
      // Messages without variables or user specified text.
      if ($variables === NULL) {
        $message = Xss::filterAdmin($row->message);
      }
      elseif (!is_array($variables)) {
        $message = $this->t('Log data is corrupted and cannot be unserialized: @message', ['@message' => Xss::filterAdmin($row->message)]);
      }
      // Message to translate with injected variables.
      else {
        $message = $this->t(Xss::filterAdmin($row->message), $variables);
      }
    }
    else {
      $message = '';
    }
    return $message;
  }

}
