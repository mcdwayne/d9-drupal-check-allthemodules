<?php

namespace Drupal\contact_onlinepbx\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\contact_onlinepbx\Controller\Api;

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
    return 'contact_onlinepbx_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['contact_onlinepbx.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setCached(FALSE);
    $config = $this->config('contact_onlinepbx.settings');

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
      '#open' => TRUE,
    ];
    $form['callnow']['from'] = [
      '#title' => $this->t('Кто'),
      '#default_value' => $config->get('from'),
      '#maxlength' => 100,
      '#type' => 'textfield',
      '#description' => 'Первый вызываемый номер',
    ];
    $form['callnow']['to'] = [
      '#title' => $this->t('Кому'),
      '#default_value' => $config->get('to'),
      '#maxlength' => 100,
      '#type' => 'textfield',
      '#description' => 'Второй вызываемый номер (только для теста), для форм подставится номер из формы.',
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
    $form['general']['fields'] = [
      '#title' => 'Fields',
      '#default_value' => $config->get('fields'),
      '#type' => 'textarea',
      '#description' => 'Технические поля из контактных форм, в которых
      будем искать телефон, например "field_form_phone"<br>1 поле на строчку',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $config = $this->config('contact_onlinepbx.settings');
    $config
      ->set('url', $form_state->getValue('url'))
      ->set('key', $form_state->getValue('key'))
      ->set('from', $form_state->getValue('from'))
      ->set('to', $form_state->getValue('to'))
      ->set('fields', $form_state->getValue('fields'))
      ->save();
  }

}
