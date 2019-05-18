<?php

namespace Drupal\recently_read\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RecentlyReadConfig.
 *
 * @package Drupal\recently_read\Form
 */
class RecentlyReadConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'delete_config',
      'delete_time',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recently_read_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('recently_read.configuration');
    $form['delete_config'] = [
      '#type' => 'radios',
      '#title' => 'Records delete options',
      '#options' => [
        'time' => $this->t('Time based'),
        'count' => $this->t('Count based'),
        'never' => $this->t('Never'),
      ],
      '#default_value' => $config->get('delete_config'),
    ];

    $delete_time_options = [
      '-1 hours' => '1 hours',
      '-1 day' => '1 day',
      '-1 week' => '1 Week',
      '-1 month' => '1 Month',
      '-1 year' => '1 Year',
    ];
    $form['delete_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Delete records older then'),
      '#description' => $this->t('When cron is executed, delete records that are older then selected value.'),
      '#options' => $delete_time_options,
      '#default_value' => $config->get('delete_time'),
      '#states' => [
        'visible' => [
          ':input[name="delete_config"]' => ['value' => 'time'],
        ],
      ],
    ];
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Max records'),
      '#description' => $this->t('Allowed number of records per user or session (if user is anonymous). Older records will be removed.'),
      '#default_value' => $config->get('count'),
      '#states' => [
        'visible' => [
          ':input[name="delete_config"]' => ['value' => 'count'],
        ],
        'required' => [
          ':input[name="delete_config"]' => ['value' => 'count'],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->getEditable('recently_read.configuration');
    $config->set('delete_config', $form_state->getValue('delete_config'));
    $config->set('delete_time', $form_state->getValue('delete_time'));
    $config->set('count', $form_state->getValue('count'));
    $config->save();
  }

}
