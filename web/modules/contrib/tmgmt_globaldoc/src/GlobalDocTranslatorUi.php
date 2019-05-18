<?php

namespace Drupal\tmgmt_globaldoc;

use Drupal\tmgmt\JobInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt_globaldoc\Plugin\tmgmt\Translator\GlobalDocTranslator;
use Drupal\tmgmt_globaldoc\Service\echoCustom;
use Drupal\tmgmt_globaldoc\Service\LangXpertService;

/**
 * GlobalDoc translator UI.
 */
class GlobalDocTranslatorUi extends TranslatorPluginUiBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();
    $form['business_unit'] = [
      '#type' => 'textfield',
      '#title' => t('Business Unit'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('business_unit'),
    ];

    $form['requester_id'] = [
      '#type' => 'textfield',
      '#title' => t('Requester ID'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('requester_id'),
    ];

    $form['security_token'] = [
      '#type' => 'textfield',
      '#title' => t('Security token'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('security_token'),
    ];

    $form['wsdl'] = [
      '#type' => 'textfield',
      '#title' => t('WSDL URL'),
      '#required' => TRUE,
      '#default_value' => $translator->getSetting('wsdl'),
    ];

    $form += parent::addConnectButton();
    $form['connect']['#submit'] = [
      [static::class, 'testConfigurationSubmit'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * Form submit callback to test the configuration.
   */
  public function testConfigurationSubmit(array &$form, FormStateInterface $form_state) {
    $echo_custom = new echoCustom('TMGMT');
    try {
      $langxpert = new LangXpertService($form_state->getValue(['settings', 'security_token']), $form_state->getValue(['settings', 'wsdl']));
      $echo_response = $langxpert->callEcho($echo_custom);
      if ($echo_response->return == 'TMGMT') {
        drupal_set_message(t('Successfully connected!'));
      }
      else {
        drupal_set_message(t('Unexpected error when connecting.'), 'error');
      }
    }
    catch (\Exception $e) {
      drupal_set_message(t('Connection failed: @error', ['@error' => $e->getMessage()]), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkoutInfo(JobInterface $job) {

    if (!$job->getReference() || !$job->isActive() || !$job->getTranslator()->getSetting('security_token')) {
      return [];
    }

    $info['task_id'] = [
      '#type' => 'item',
      '#title' => t('Task ID'),
      '#markup' => $job->getReference(),
    ];

    $task_status = $job->getTranslatorPlugin()->getTaskState($job->getTranslator(), $job->getReference());
    $info['task_state'] = [
      '#type' => 'item',
      '#title' => t('Task state'),
      '#markup' => $task_status->return,
    ];

    if ($task_status->return == GlobalDocTranslator::STATUS_SOURCE_COMPLETED) {
      $info['fetch'] = [
        '#type' => 'submit',
        '#value' => t('Fetch translation'),
        '#submit' => [
          [static::class, 'fetchTranslation']
        ]
      ];
    }

    return $info;
  }

  /**
   * Form submit callback to fetch translations.
   */
  public static function fetchTranslation($form, FormStateInterface $form_state) {
    /** @var \Drupal\tmgmt\JobInterface $job */
    $job = $form_state->getFormObject()->getEntity();
    $job->getTranslatorPlugin()->fetchTranslation($job);

    tmgmt_write_request_messages($job);
  }

}
