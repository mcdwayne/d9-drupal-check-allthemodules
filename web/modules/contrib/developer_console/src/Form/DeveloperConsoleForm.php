<?php

namespace Drupal\developer_console\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

/**
 * Console form definition.
 */
class DeveloperConsoleForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dev_console_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $form['input_type'] = [
      '#type' => 'radios',
      '#title' => t('Syntax'),
      '#options' => [
        'PHP' => t('PHP code'),
        'SQL' => t('DB query')
      ],
      '#attributes' => ['class' => ['input-type-selector']],
      '#default_value' => isset($values['input_type']) ? $values['input_type'] : 'PHP'
    ];
    $form['input'] = [
      '#type' => 'textarea',
      '#title' => t('Code'),
      '#rows' => 20,
      '#default_value' => isset($values['input']) ? $values['input'] : '',
      '#attributes' => [
        'autocomplete' => 'off',
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],

    ];
    $form['save_entry'] = [
      '#type' => 'checkbox',
      '#title' => t('save in history'),
      '#default_value' => isset($values['save_entry']) ? $values['save_entry'] : 1
    ];
    $form['execute'] = [
      '#type' => 'submit',
      '#value' => t('Execute')
    ];

    $storage = $form_state->getStorage();
    if (isset($storage['results'])) {
      $form['result'] = [
        '#type' => 'fieldset',
        '#title' => t('Results')
      ];
      $form['result']['time'] = [
        '#markup' => '<div><h4>' . t('Execution time') . ':</h4><p>' . $storage['results']['time'] . ' ms</p></div>'
      ];
      if (isset($storage['results']['return'])) {
        $form['result']['return'] = [
          '#markup' => '<div>' . $storage['results']['return'] . '</div>'
        ];
      }
      if (isset($storage['results']['print'])) {
        $form['result']['print'] = [
          '#markup' => '<div>' . $storage['results']['print'] . '</div>'
        ];
      }
    }

    // Console history.
    $history_arr = [];
    $result = db_select('developer_console_history', 'h')->fields('h', [
      'hid',
      'type',
      'input'
    ])->orderBy('hid', 'DESC')->execute();
    while ($data = $result->fetchAssoc()) {
      $history_arr[$data['type']][] = [
        'hid' => $data['hid'],
        'input' => $data['input']
      ];
    }

    $history_html = '<div class="console-history">';
    foreach ($history_arr as $type => $data) {
      $history_html .= '<ul id="console-history-' . $type . '">';
      foreach ($data as $element) {
        $history_html .= '<li><a href="javascript:void(0)" class="console-history-selector">+</a><pre class="input" style="display: inline-block;">' . $element['input'] . '</pre></li>';
      }
      $history_html .= '</ul>';
    }
    $history_html .= '</div>';

    $form['history'] = [
      '#markup' => $history_html
    ];

    $form['#attached']['library'][] = 'developer_console/ui';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('input'))) {
      $form_state->setError($form['input'], $this->t('Input is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $storage = [];

    // Code execution and timetracking.
    $input_type = $form_state->getValue('input_type');
    $input = $form_state->getValue('input');
    switch ($input_type) {
      case 'PHP':
        ini_set('display_errors', 'stderr');
        ob_start();

        $start_time = microtime(TRUE);
        try {
          $storage['results']['result'] = eval($input);
        }
        catch (Exception $e) {
          echo 'Error: ' . print_r($e, TRUE);
        }
        $end_time = microtime(TRUE);

        $storage['results']['print'] = ob_get_contents();
        ob_end_clean();
        break;

      case 'SQL':
        $connection = Database::getConnection();

        $start_time = microtime(TRUE);
        try {
          $result = $connection->query($input);
        }
        catch (Exception $e) {
          $result = kdpm($e, 'SA');
        }
        $end_time = microtime(TRUE);
        break;
    }

    // Process results.
    switch ($input_type) {
      case 'PHP':
        if (isset($storage['results']['return'])) {
          $storage['results']['return'] = '<h4>' . t('Returned value') . ':</h4><p>' . $storage['results']['return'] . '</p>';
        }
        if (isset($storage['results']['print'])) {
          $storage['results']['print'] = '<h4>' . t('Printout') . ':</h4><p><pre>' . $storage['results']['print'] . '</pre></p>';
        }
        break;

      case 'SQL':
        if (is_object($result)) {
          $rows = [];
          while ($row = $result->fetchAssoc()) {
            $rows[] = $row;
          }

          $result->allowRowCount = TRUE;
          $storage['results']['return'] = '<p><span>' . t('Affected rows') . ': </span>' . $result->rowCount() . '</p>';
          $storage['results']['print'] = $this->table($rows);
        }
        else {
          $storage['results']['print'] = '<h3>Error: </h3>' . '<pre>' . $result . '</pre>';
        }
        break;
    }
    $storage['results']['time'] = round(($end_time - $start_time) * 1000, 3);

    // Record input in history.
    if ($form_state->getValue('save_entry')) {
      $nmax = 10;
      do {
        $ntype = db_select('developer_console_history', 'h')
          ->fields('h', ['hid'])
          ->condition('type', $input_type)
          ->countQuery()
          ->execute()
          ->fetchField();

        if ($ntype >= $nmax) {
          $hid = db_select('developer_console_history', 'h')
            ->fields('h', ['hid'])
            ->condition('type', $input_type)
            ->orderBy('hid', 'ASC')
            ->range(0, 1)
            ->execute()
            ->fetchField();

          db_delete('developer_console_history')->condition('hid', $hid)->execute();
        }
      } while ($ntype >= $nmax);

      // Avoid saving identical entries multiple times.
      $last_entry = db_select('developer_console_history', 'h')
        ->fields('h', ['input'])
        ->condition('type', $input_type)
        ->orderBy('hid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($last_entry != $input) {
        // Save history item.
        db_insert('developer_console_history')->fields([
          'type' => $input_type,
          'input' => $input,
        ])->execute();
      }
    }

    $form_state->setStorage($storage);
  }

  /**
   * Helper function to render results table.
   */
  protected function table($data) {
    $renderable = [
      '#theme' => 'table',
      '#title' => $this->t('Results:'),
      '#header' => [],
      '#rows' => [],
      '#empty' => $this->t('No results.')
    ];

    foreach ($data[0] as $key => $obsolete) {
      $renderable['#header'][$key] = $key;
    }

    foreach ($data as $key => $row) {
      foreach ($renderable['#header'] as $title) {
        $renderable['#rows'][$key][]['data'] = isset($row[$title]) ? $row[$title] : '';
      }
    }

    $renderer = \Drupal::service('renderer');
    return $renderer->render($renderable);
  }

}
