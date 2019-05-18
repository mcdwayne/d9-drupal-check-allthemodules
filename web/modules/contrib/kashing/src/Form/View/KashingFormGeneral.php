<?php

namespace Drupal\kashing\form\View;

use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\kashing\Entity\KashingValid;
use Drupal\kashing\misc\currency\KashingCurrency;

/**
 * Kashing Form General class.
 */
class KashingFormGeneral {

  private $kashingCurrency;

  /**
   * Construct.
   */
  public function __construct() {
    $this->kashingCurrency = new KashingCurrency();
  }

  /**
   * General Page content.
   */
  public function addGeneralPage(array &$form) {

    $config = \Drupal::config('kashing.settings');

    $form['general_mode'] = [
      '#type' => 'details',
      '#group' => 'kashing_settings',
      '#title' => t('General'),
    ];

    $form['general_mode']['currency'] = [
      '#type' => 'fieldset',
      '#title' => t('Choose Currency'),
      '#description' => t('Choose a currency for your payments.'),
    ];

    // $kashingCurrency = new KashingCurrency();
    $form['general_mode']['currency']['currency_select'] = [
      '#type' => 'select',
      '#options' => [
        'GBP' => t("British Pounds Sterling"),
        'USD' => t("United States Dollars"),
        'EUR' => t("Euro"),
      ]
                // $this->kashingCurrency->get_all()
      ,
      '#default_value' => $config->get('currency') ? $config->get('currency') : 'GBP',
      '#attributes' => [
        'id' => 'kashing-general-currency',
      ],
    ];

    $form['general_mode']['success_page'] = [
      '#type' => 'fieldset',
      '#title' => t('Success Page'),
      '#description' => t('Create the page your clients will be redirected to after the payment is successful.'),
    ];

    $form['general_mode']['success_page']['success_page_editor'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#wysiwyg' => TRUE,
      '#rows' => 7,
      '#default_value' => $config->get('success_page') ? $config->get('success_page') : '',
    ];

    $form['general_mode']['failure_page'] = [
      '#type' => 'fieldset',
      '#title' => t('Failure Page'),
      '#description' => t('Create the page your clients will be redirected to after the payment failed.'),
    ];

    $form['general_mode']['failure_page']['failure_page_editor'] = [
      '#type' => 'text_format',
      '#format' => 'full_html',
      '#wysiwyg' => TRUE,
      '#rows' => 7,
      '#default_value' => $config->get('failure_page') ? $config->get('failure_page') : '',
    ];

    $form['general_mode']['actions']['submit'] = [
      '#type' => 'button',
      '#name' => 'general_mode_submit_button_name',
      '#value' => t('Save changes'),
      '#ajax' => [
        'callback' => 'Drupal\kashing\form\View\KashingFormGeneral::submitGeneral',
        '#wrapper' => 'kashing-general-form-result',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
      '#suffix' => '<div id="kashing-general-form-result"></div>',
    ];
  }

  /**
   * General page submit.
   */
  public function submitGeneral(array &$form, FormStateInterface $form_state) {

    $configuration_errors = FALSE;
    $error_info = '<strong>' . t('Missing fields:') . ' </strong><ul>';
    $ajax_response = new AjaxResponse();

    $currency = $form_state->getValue('currency_select');
    // $success_page = $form_state->getValue('success_page_select');
    // $failure_page = $form_state->getValue('failure_page_select');.
    $kashing_validate = new KashingValid();

    // Currency.
    if (!$kashing_validate->validateRequiredField($currency)) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-general-currency', 'addClass', ['error']));
      $configuration_errors = 'true';
      $error_info .= '<li>' . t('Currency') . '</li>';
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-general-currency', 'removeClass', ['error']));
    }

    // Display any errors or save configuration.
    if ($configuration_errors) {
      $ajax_response->addCommand(new InvokeCommand('#kashing-general-form-result', 'removeClass', ['messages--status messages']));
      $ajax_response->addCommand(new InvokeCommand('#kashing-general-form-result', 'addClass', ['messages--error messages']));
      $ajax_response->addCommand(new HtmlCommand('#kashing-general-form-result', $error_info));
    }
    else {
      $ajax_response->addCommand(new InvokeCommand('#kashing-general-form-result', 'removeClass', ['messages--error messages']));
      $ajax_response->addCommand(new HtmlCommand('#kashing-general-form-result', t('General settings saved!')));
      $ajax_response->addCommand(new InvokeCommand('#kashing-general-form-result', 'addClass', ['messages--status messages']));
      KashingFormGeneral::generalSubmitProcess($form, $form_state);
    }

    return $ajax_response;
  }

  /**
   * General page submit process.
   */
  public static function generalSubmitProcess(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')->getEditable('kashing.settings');

    $currency = $form_state->getValue('currency_select');
    if ($currency) {
      $config->set('currency', $currency);
    }

    $success_page = $form_state->getValue('success_page_editor');
    if ($success_page) {
      $config->set('success_page', $success_page['value']);
    }

    $failure_page = $form_state->getValue('failure_page_editor');
    if ($failure_page) {
      $config->set('failure_page', $failure_page['value']);
    }

    $config->save();
  }

}
