<?php

namespace Drupal\date_recur_status\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class StatusAdminForm.
 *
 * @package Drupal\date_recur_status\Form
 */
class StatusAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'date_recur_status_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'date_recur_status.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('date_recur_status.settings');
    $statuses = $config->get('statuses');
    $status_text = '';
    foreach ($statuses as $key => $value) {
      $status_text .= "$key|$value\n";
    }
    $form['statuses'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Available statuses'),
      '#default_value' => $status_text,
      '#description' => $this->t('Enter one status per line. Seperate key (saved in database) and value (displayed) by a pipe (|). The first row is the default status.')
    ];

    $skip_index = $config->get('skip_index') ?: [];
    $form['filter'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#title' => $this->t('Filter occurrences by status'),
      '#description' => $this->t('Occurrences with the selected statuses will not be indexed by Search API. <em>Note: This setting has no effect if Search API is not used. If using regular views, filter by the provided status field directly.</em>')
    ];
    $form['filter']['skip_index'] = [
      '#type' => 'checkboxes',
      '#options' => $statuses,
      '#default_value' => $skip_index,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $statuses = explode("\n", str_replace("\r\n", "\n", $form_state->getValue('statuses')));
    $list = [];
    foreach ($statuses as $item) {
      if (!empty(trim($item))) {
        $item = explode('|', trim($item));
        if (count($item) === 1) {
          $list[$item[0]] = $item[0];
        }
        if (count($item) === 2) {
          $list[$item[0]] = $item[1];
        }
      }
    }
    $skip_index = array_keys(array_filter($form_state->getValue('skip_index')));
    $this->config('date_recur_status.settings')
      ->set('statuses', $list)
      ->set('skip_index', $skip_index)
      ->save();
  }
}
