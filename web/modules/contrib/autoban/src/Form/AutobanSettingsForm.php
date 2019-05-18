<?php

namespace Drupal\autoban\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\autoban\Controller\AutobanController;

/**
 * Configure autoban settings for this site.
 */
class AutobanSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autoban_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'autoban.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autoban.settings');

    // Retrieve Ban manager list.
    $providers = [];
    $controller = new AutobanController();
    $banManagerList = $controller->getBanProvidersList();
    if (!empty($banManagerList)) {
      foreach ($banManagerList as $id => $item) {
        $providers[$id] = $item['name'];
      }
      $form['providers'] = [
        '#markup' => '<label>' . t('Autoban providers') . '</label> ' . implode(', ', $providers),
        '#allowed_tags' => ['label',],
      ];
    }
    else {
      drupal_set_message(
        $this->t('List ban providers is empty. You have to enable at least one Autoban providers module.'),
        'warning'
      );
    }

    $thresholds = $config->get('autoban_thresholds') ?: "1\n2\n3\n5\n10\n20\n50\n100";
    $form['autoban_thresholds'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Thresholds'),
      '#default_value' => $thresholds,
      '#required' => TRUE,
      '#description' => $this->t('Thresholds set for Autoban rules threshold field.'),
    ];

    $query_mode = $config->get('autoban_query_mode') ?: 'like';
    $form['autoban_query_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Query mode'),
      '#options' => ['like' => 'LIKE', 'regexp' => 'REGEXP'],
      '#default_value' => $query_mode,
      '#description' => $this->t('Use REGEXP option if your SQL engine supports REGEXP syntax.'),
    ];

    $form['autoban_whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Whitelist'),
      '#default_value' => $config->get('autoban_whitelist'),
      '#description' => $this->t('Enter a list of IP addresses or domain. Format: CIDR "aa.bb.cc.dd/ee" or "aa.bb.cc.dd" or "googlebot.com". # symbol use as a comment.
        The rows beginning with # are comments and are ignored.
        For example: <a href="http://www.iplists.com/google.txt" rel="nofollow" target="_new">robot-whitelist site</a>.'),
      '#rows' => 10,
      '#cols' => 30,
    ];

    $dblog_type_exclude = $config->get('autoban_dblog_type_exclude') ?: "autoban\ncron\nphp\nsystem\nuser";
    $form['autoban_dblog_type_exclude'] = [
      '#type' => 'textarea',
      '#title' => t('Exclude dblog types'),
      '#default_value' => $dblog_type_exclude,
      '#description' => t('Exclude dblog types events for log analyze.'),
    ];

    $form['autoban_threshold_analyze'] = [
      '#type' => 'number',
      '#title' => t('Analyze\'s form threshold'),
      '#default_value' => $config->get('autoban_threshold_analyze') ?: 5,
      '#description' => t('Threshold for log analyze.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $thresholds = explode("\n", $form_state->getValue('autoban_thresholds'));
    foreach ($thresholds as $threshold) {
      $threshold = intval(trim($threshold));
      if (empty($threshold) || $threshold <= 0) {
        $form_state->setErrorByName('autoban_thresholds', $this->t('Threshold values must be a positive integer.'));
      }
    }

    $dblog_type_exclude = explode("\n", $form_state->getValue('autoban_dblog_type_exclude'));
    foreach ($dblog_type_exclude as $item) {
      $item = trim($item);
      if (empty($item)) {
        $form_state->setErrorByName('autoban_dblog_type_exclude', $this->t('Dblog type exclude cannot be empty.'));
      }
    }

    if ($form_state->getValue('autoban_threshold_analyze') <= 0) {
      $form_state->setErrorByName('autoban_threshold_analyze', $this->t('Analyze\'s form threshold must be a positive integer.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // To ensure that the threshold values are integers.
    $thresholds = explode("\n", $form_state->getValue('autoban_thresholds'));
    array_walk($thresholds, function(&$item, $key) {
      $item = (int) $item;
    });

   // To ensure that the dblog_type_exclude values was trimmed.
    $dblog_type_exclude = explode("\n", $form_state->getValue('autoban_dblog_type_exclude'));
    array_walk($dblog_type_exclude, function(&$item, $key) {
      $item = trim($item);
    });

    \Drupal::configFactory()->getEditable('autoban.settings')
      ->set('autoban_thresholds', implode("\n", $thresholds))
      ->set('autoban_query_mode', $form_state->getValue('autoban_query_mode'))
      ->set('autoban_whitelist', $form_state->getValue('autoban_whitelist'))
      ->set('autoban_dblog_type_exclude', implode("\n", $dblog_type_exclude))
      ->set('autoban_threshold_analyze', $form_state->getValue('autoban_threshold_analyze'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
