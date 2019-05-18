<?php

namespace Drupal\letsencrypt\Form;

/**
 * @file
 * Contains Drupal\letsencrypt\Form\Settings.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\letsencrypt\Utility\PrepareDomain;

/**
 * Implements controller.
 */
class Settings extends ConfigFormBase {

  /**
   * Ajax Sign callback event.
   */
  public function ajaxSign($form, $form_state) {
    $response = new AjaxResponse();
    $base = $form_state->getValue('domain-base');
    $domains = PrepareDomain::init($form_state->getValue('domain-domains'), TRUE);
    $output = "ajaxSign: <b>{$base}</b>\n";
    $le = \Drupal::service('letsencrypt')->sign($base, $domains);
    $output = $le . "\n";
    $response->addCommand(new HtmlCommand('#le-wrap', "<pre>{$output}</pre>"));
    return $response;
  }

  /**
   * Ajax Read callback event.
   */
  public function ajaxRead($form, $form_state) {
    $response = new AjaxResponse();
    $base = $form_state->getValue('domain-base');
    $output = \Drupal::service('letsencrypt')->read($base);
    $response->addCommand(new HtmlCommand('#le-wrap', "<pre>{$output}</pre>"));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {
    $config = $this->config('letsencrypt.settings');

    $form_state->setCached(FALSE);

    $domain = $config->get('domain-base');
    if (!$domain) {
      $domain = str_replace('www.', '', $_SERVER['HTTP_HOST']);
    }
    $domanis = $config->get('domain-domains');
    if (!$domanis) {
      $domanis = implode("\n", [$domain, "www.{$domain}"]);
    }

    // Settings.
    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => FALSE,
    ];

    $form['settings']['cert_dir'] = [
      '#title' => $this->t('Certificates Dir'),
      '#type' => 'textfield',
      '#default_value' => $config->get('cert-dir'),
      '#description' => $this->t("private://"),
    ];
    $form['settings']['cert_email'] = [
      '#title' => $this->t('E-mail'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config->get('cert-email'),
    ];
    $form['settings']['cron'] = [
      '#title' => $this->t('Update certificate by cron'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cron'),
      '#description' => $this->t("20 days before expire."),
    ];
    $form['settings']['acme_url'] = [
      '#title' => $this->t('ACME URL'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => [
        'stage' => $this->t('Test ENV: @u', ['@u' => 'https://acme-staging-v02.api.letsencrypt.org']),
        'prod' => $this->t('Production ENV: @u', ['@u' => 'https://acme-v02.api.letsencrypt.org']),
      ],
      '#default_value' => $config->get('acme-url'),
    ];
    $form['settings']['acme_log'] = [
      '#title' => $this->t('The level of logging'),
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => [
        'LOG_OFF' => $this->t('Logs no messages or faults, except Runtime Exceptions'),
        'LOG_STATUS' => $this->t('Logs only messages and faults'),
        'LOG_DEBUG' => $this->t('Logs messages, faults and raw responses from HTTP requests'),
      ],
      '#default_value' => $config->get('acme-log'),
    ];

    // Exec.
    $form['cert-create'] = [
      '#type' => 'details',
      '#title' => t('Create Cert'),
      '#open' => TRUE,
    ];
    $form['cert-create']['domain-base'] = [
      '#title' => $this->t('Base domain'),
      '#type' => 'textfield',
      '#default_value' => $domain,
      '#required' => TRUE,
    ];
    $form['cert-create']['domain-domains'] = [
      '#title' => $this->t('All domains'),
      '#type' => 'textarea',
      '#default_value' => $domanis,
    ];
    $form['cert-create']['actions'] = [
      '#type' => 'actions',
      '#suffix' => '<div id="le-wrap"></div>',
      'sign' => [
        '#type' => 'submit',
        '#attributes' => ['class' => ['btn', 'btn-success']],
        '#value' => $this->t('Create New Cert'),
        '#ajax'   => [
          'callback' => '::ajaxSign',
          'effect'   => 'fade',
          'progress' => ['type' => 'throbber', 'message' => NULL],
        ],
      ],
      'read' => [
        '#type' => 'submit',
        '#attributes' => ['class' => ['btn', 'btn-success']],
        '#value' => $this->t('Read Cert'),
        '#ajax'   => [
          'callback' => '::ajaxRead',
          'effect'   => 'fade',
          'progress' => ['type' => 'throbber', 'message' => NULL],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $config = $this->config('letsencrypt.settings');
    // Domain-base.
    $domain = $form_state->getValue('domain-base');
    $base = trim($domain);
    // Domains.
    $domains = PrepareDomain::init($form_state->getValue('domain-domains'), TRUE);
    $config
      ->set('cert-dir', $form_state->getValue('cert_dir'))
      ->set('cert-email', $form_state->getValue('cert_email'))
      ->set('acme-url', $form_state->getValue('acme_url'))
      ->set('acme-log', $form_state->getValue('acme_log'))
      ->set('cron', $form_state->getValue('cron'))
      ->set('domain-base', $base)
      ->set('domain-domains', implode("\n", $domains))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['letsencrypt.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'letsencrypt_settings';
  }

}
