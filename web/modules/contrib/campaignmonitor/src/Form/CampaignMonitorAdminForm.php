<?php

namespace Drupal\campaignmonitor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure campaignmonitor settings for this site.
 */
class CampaignMonitorAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_admin_settings';
  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('campaignmonitor.settings');

    $cm_api_url = Url::fromUri('https://help.campaignmonitor.com/topic.aspx?t=206',
      ['attributes' => ['target' => '_blank']]);

    $form['campaignmonitor_account'] = [
      '#type' => 'fieldset',
      '#title' => t('Account details'),
      '#description' => t('Enter your Campaign Monitor account information. See @link for more information.', ['@link' => \Drupal::l(t('the Campaign Monitor API documentation'), $cm_api_url)]),
      // '#collapsible' => empty($config) ? FALSE : TRUE,
      // '#collapsed' => empty($config) ? FALSE : TRUE,.
      '#tree' => TRUE,
    ];

    $form['campaignmonitor_account']['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#description' => t('Your Campaign Monitor API Key. See <a href="http://www.campaignmonitor.com/topic.aspx?t=206">documentation</a>.'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
      '#size' => 250,
      '#maxlength' => 250,
    ];

    $form['campaignmonitor_account']['client_id'] = [
      '#type' => 'textfield',
      '#title' => t('Client ID'),
      '#description' => t('Your Campaign Monitor Client ID. See <a href="http://www.campaignmonitor.com/topic.aspx?t=206">documentation</a>.'),
      '#default_value' => $config->get('client_id') != NULL ? $config->get('client_id') : '',
      '#required' => TRUE,
    ];

    if ($config->get('client_id') != NULL) {

      $form['campaignmonitor_general'] = [
        '#type' => 'fieldset',
        '#title' => t('General settings'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#tree' => TRUE,
      ];

      $form['campaignmonitor_general']['cache_timeout'] = [
        '#type' => 'textfield',
        '#title' => t('Cache timeout'),
        '#description' => t('Cache timeout in seconds for stats, subscribers and archive information.'),
        '#size' => 4,
        '#default_value' => $config->get('cache_timeout') != NULL ? $config->get('cache_timeout') : '360',
      ];

      $form['campaignmonitor_general']['archive'] = [
        '#type' => 'checkbox',
        '#title' => t('Newsletter archive'),
        '#description' => t('Create a block with links to HTML versions of past campaigns.'),
        '#default_value' => $config->get('archive') != NULL ? $config->get('archive') : 0,
      ];

      $form['campaignmonitor_general']['logging'] = [
        '#type' => 'checkbox',
        '#title' => t('Log errors'),
        '#description' => t('Log communication errors with the Campaign Monitor service, if any.'),
        '#default_value' => $config->get('logging') != NULL ? $config->get('logging') : 0,
      ];

      $form['campaignmonitor_general']['instructions'] = [
        '#type' => 'textfield',
        '#title' => t('Newsletter instructions'),
        '#description' => t('This message will be displayed to the user when subscribing to newsletters.'),
        '#default_value' => $config->get('instructions') != NULL ? $config->get('instructions') : t('Select the
        newsletters you want to subscribe to.'),
      ];

      // Add cache clear button.
      $form['clear_cache'] = [
        '#type' => 'fieldset',
        '#title' => t('Clear cached data'),
        '#description' => t('The information downloaded from Campaign Monitor is cached to speed up the website. The lists details, custom fields and other data may become outdated if these are changed at Campaign Monitor. Clear the cache to refresh this information.'),
      ];

      $form['clear_cache']['clear'] = [
        '#type' => 'submit',
        '#value' => t('Clear cached data'),
        '#submit' => ['campaignmonitor_clear_cache_submit'],
      ];
    }

    $form['cron'] = [
      '#type' => 'checkbox',
      '#title' => 'Use batch processing.',
      '#description' => 'Puts all campaignmonitor subscription operations into the cron queue. (Includes subscribe, update, and unsubscribe operations.) <i>Note: May cause confusion if caches are cleared, as requested changes will appear to have failed until cron is run.</i>',
      '#default_value' => $config->get('cron'),
    ];
    $form['batch_limit'] = [
      '#type' => 'select',
      '#options' => [
        '1' => '1',
        '10' => '10',
        '25' => '25',
        '50' => '50',
        '75' => '75',
        '100' => '100',
        '250' => '250',
        '500' => '500',
        '750' => '750',
        '1000' => '1000',
        '2500' => '2500',
        '5000' => '5000',
        '7500' => '7500',
        '10000' => '10000',
      ],
      '#title' => t('Batch limit'),
      '#description' => t('Maximum number of entities to process in a single cron run. campaignmonitor suggest keeping this at 5000 or below. <i>This value is also used for batch Merge Variable updates on the Fields tab (part of campaignmonitor_lists).</i>'),
      '#default_value' => $config->get('batch_limit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor.settings');
    $config
      ->set('api_key', $form_state->getValue([
        'campaignmonitor_account',
        'api_key',
      ]))
      ->set('client_id', $form_state->getValue([
        'campaignmonitor_account',
        'client_id',
      ]))
      ->set('cache_timeout', $form_state->getValue([
        'campaignmonitor_general',
        'cache_timeout',
      ]))
      ->set('library_path', $form_state->getValue([
        'campaignmonitor_general',
        'library_path',
      ]))
      ->set('archive', $form_state->getValue([
        'campaignmonitor_general',
        'archive',
      ]))
      ->set('logging', $form_state->getValue([
        'campaignmonitor_general',
        'logging',
      ]))
      ->set('instructions', $form_state->getValue([
        'campaignmonitor_general',
        'instructions',
      ]))
      ->set('cron', $form_state->getValue('cron'))
      ->set('batch_limit', $form_state->getValue('batch_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
