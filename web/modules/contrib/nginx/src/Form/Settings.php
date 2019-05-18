<?php

namespace Drupal\nginx\Form;

/**
 * @file
 * Contains Drupal\nginx\Form\Settings.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Implements controller.
 */
class Settings extends ConfigFormBase {

  /**
   * Ajax nginx.conf callback event.
   */
  public function ajaxNginxConf($form, $form_state) {
    $response = new AjaxResponse();
    $conf = \Drupal::service('nginx.conf')->get();
    $output = "ajaxNginxConf:\n{$conf}\n";
    $response->addCommand(new HtmlCommand('#nginx-wrap', "<pre>{$output}</pre>"));
    return $response;
  }

  /**
   * Ajax example.org callback event.
   */
  public function ajaxNginxSite($form, $form_state) {
    $response = new AjaxResponse();
    $output = "ajaxNginxSite:\n";
    $domains = $this->getDomains($form_state);
    $conf = \Drupal::service('nginx.site')->get($domains);
    $output .= $conf['http'];
    $output .= $conf['https'];
    $response->addCommand(new HtmlCommand('#nginx-wrap', "<pre>{$output}</pre>"));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $extra = NULL) {
    $config = $this->config('nginx.settings');

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
      '#suffix' => '<div id="nginx-wrap"></div>',
      'sign' => [
        '#type' => 'submit',
        '#attributes' => ['class' => ['btn', 'btn-success']],
        '#value' => $this->t('site.conf'),
        '#ajax'   => [
          'callback' => '::ajaxNginxSite',
          'effect'   => 'fade',
          'progress' => ['type' => 'throbber', 'message' => NULL],
        ],
      ],
      'nginx-conf' => [
        '#type' => 'submit',
        '#attributes' => ['class' => ['btn', 'btn-success']],
        '#value' => $this->t('nginx.conf'),
        '#ajax'   => [
          'callback' => '::ajaxNginxConf',
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
    $config = $this->config('nginx.settings');
    $config
      ->set('dir', $form_state->getValue('dir'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDomains($form_state) {
    $name = $form_state->getValue('domain-base');
    $domains = $form_state->getValue('domain-domains');
    $domains = str_replace([' ', "\n", "\t"], ",", $domains);
    $result = [$name => $name];
    foreach (explode(",", $domains) as $domain) {
      $domain = trim($domain);
      if (strlen($domain) > 3) {
        $result[$domain] = $domain;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nginx.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nginx_settings';
  }

}
