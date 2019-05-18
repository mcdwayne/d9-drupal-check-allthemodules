<?php

namespace Drupal\onlinepbx\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\onlinepbx\Controller\Api;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  /**
   * Ajax Callback - TestConnection.
   */
  public function ajaxTestConnection(array &$form, $form_state) {
    $otvet = Api::test();
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#wrapper-test-connection", "<pre>$otvet</pre>"));
    return $response;
  }

  /**
   * Ajax CallNow.
   */
  public function ajaxCallNow(array &$form, $form_state) {
    $to = $form_state->getValue('to');
    $otvet = Api::callNow($to);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#wrapper-call-now", "<pre>$otvet</pre>"));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onlinepbx_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['onlinepbx.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setCached(FALSE);
    $config = $this->config('onlinepbx.settings');

    $form['connection'] = [
      '#type' => 'details',
      '#title' => $this->t('OnlinePbx Connection'),
      '#open' => TRUE,
    ];
    if ($config->get('url')) {
      $form['connection']['#open'] = FALSE;
    }
    $form['connection']['url'] = [
      '#title' => $this->t('URL'),
      '#default_value' => $config->get('url'),
      '#maxlength' => 100,
      '#type' => 'textfield',
      '#description' => $this->t('[example.onpbx.ru]. You can create account here
        <a target="_blank" href="https://www.onlinepbx.ru/">OnlinePBX</a>'),
    ];
    $form['connection']['key'] = [
      '#title' => $this->t('API-key'),
      '#default_value' => $config->get('key'),
      '#maxlength' => 100,
      '#type' => 'textfield',
      '#description' => $this->t('Find your key
        <a target="_blank" href="https://panel2.onlinepbx.ru/setup.php">here</a>'),

    ];
    $form['connection']['actions'] = [
      '#type' => 'actions',
      'test' => [
        '#type' => 'submit',
        '#value' => $this->t('Test Connection [get -1day history]'),
        '#attributes' => ['class' => ['btn', 'btn-xs']],
        '#ajax'   => [
          'callback' => '::ajaxTestConnection',
          'effect'   => 'fade',
          'progress' => ['type' => 'throbber', 'message' => ""],
        ],
        '#suffix' => "<br><div id='wrapper-test-connection'></div>",
      ],
    ];
    $form['callnow'] = [
      '#type' => 'details',
      '#title' => $this->t('Call Now'),
      '#open' => FALSE,
    ];
    $form['callnow']['from'] = [
      '#title' => $this->t('From'),
      '#default_value' => $config->get('from'),
      '#maxlength' => 100,
      '#type' => 'textfield',
      '#description' => 'First called number',
    ];
    $form['callnow']['to'] = [
      '#title' => $this->t('To'),
      '#default_value' => $config->get('to'),
      '#maxlength' => 100,
      '#type' => 'textfield',
      '#description' => 'The second called number',
    ];
    $form['callnow']['actions'] = [
      '#type' => 'actions',
      'call' => [
        '#type' => 'submit',
        '#value' => $this->t('Call Now'),
        '#attributes' => ['class' => ['btn', 'btn-xs']],
        '#ajax'   => [
          'callback' => '::ajaxCallNow',
          'effect'   => 'fade',
          'progress' => ['type' => 'throbber', 'message' => ""],
        ],
        '#suffix' => "<br><div id='wrapper-call-now'></div>",
      ],
    ];
    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display'),
      '#open' => FALSE,
    ];
    $form['display']['limit'] = [
      '#title' => $this->t('Limit'),
      '#default_value' => $config->get('limit'),
      '#type' => 'number',
      '#description' => 'The call did not take place, seconds.',
    ];
    $form['display']['gateways'] = [
      '#title' => $this->t('Gateway'),
      '#default_value' => $config->get('gateways'),
      '#type' => 'textarea',
      '#description' => 'Gateway replace YAML config',
    ];
    $form['display']['users'] = [
      '#title' => $this->t('Users'),
      '#default_value' => $config->get('users'),
      '#type' => 'textarea',
      '#description' => 'Users replace YAML config',
    ];
    $form['callback'] = [
      '#type' => 'details',
      '#title' => $this->t('HTTP callback'),
      '#open' => FALSE,
    ];
    $host = \Drupal::request()->getHost();
    $scheme = \Drupal::request()->getScheme();
    $path = "{$scheme}://{$host}/modules/custom/onlinepbx/callback.php";
    $form['callback']['transfer-default'] = [
      '#title' => $this->t('Default Transfer'),
      '#default_value' => $config->get('transfer-default'),
      '#type' => 'number',
    ];
    $form['callback']['transfer'] = [
      '#title' => $this->t('YAML Transfer Rules'),
      '#default_value' => $config->get('transfer'),
      '#type' => 'textarea',
      '#description' => $this->t('OnPBX http-callback @path', ['@path' => $path]),
    ];
    $form['callback']['black-on'] = [
      '#title' => $this->t('Blacklist enable'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('black-on'),
    ];
    $form['callback']['black-phones'] = [
      '#title' => $this->t('Ban income phones'),
      '#default_value' => $config->get('black-phones'),
      '#type' => 'textarea',
      '#description' => 'One item per line',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $config = $this->config('onlinepbx.settings');
    $config
      ->set('url', $form_state->getValue('url'))
      ->set('key', $form_state->getValue('key'))
      ->set('gateways', $form_state->getValue('gateways'))
      ->set('users', $form_state->getValue('users'))
      ->set('limit', $form_state->getValue('limit'))
      ->set('transfer', $form_state->getValue('transfer'))
      ->set('transfer-default', $form_state->getValue('transfer-default'))
      ->set('black-on', $form_state->getValue('black-on'))
      ->set('black-phones', $form_state->getValue('black-phones'))
      ->save();
  }

}
