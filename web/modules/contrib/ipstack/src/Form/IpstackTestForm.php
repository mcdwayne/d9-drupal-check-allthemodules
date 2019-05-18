<?php

namespace Drupal\ipstack\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\ipstack\Ipstack;

/**
 * Configure ipstack settings for this site.
 */
class IpstackTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ipstack_admin_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('ipstack.settings');

    $user_ip = \Drupal::request()->getClientIp();
    $ip = $config->get('ip');
    if (empty($ip)) {
      $ip = $user_ip;
    }
    $form['ip'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IP address'),
      '#default_value' => $ip,
      '#required' => TRUE,
      '#description' => $this->t("IP address for test retrieving
        <a href='@url' rel='nofollow' target='_new'>ipstack.com</a> data.
        Your current IP: @user_ip .",
        ['@url' => 'https://ipstack.com', '@user_ip' => $user_ip]
      ),
    ];

    $fields = [
      'main' => $this->t('(main) returns main API objects from "ip" to "longitude"'),
      'country_code' => $this->t('(country_code) returns only the "country_code" object'),
      'location' => $this->t('(location) returns the entire "location" object with sub-objects'),
      'location.capital ' => $this->t('(location.capital) returns only the "capital" object inside "location"'),
    ];
    $form['fields'] = [
      '#type' => 'select',
      '#title' => $this->t('Response Fields'),
      '#required' => FALSE,
      '#multiple' => TRUE,
      '#options' => $fields,
      '#default_value' => $config->get('fields'),
      '#description' => $this->t("Specify Response Fields. Look details at
        <a href='@url' rel='nofollow' target='_new'>documentation</a>.",
        ['@url' => 'https://ipstack.com/documentation#fields']
      ),
    ];

    $form['hostname'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Hostname Lookup'),
      '#required' => FALSE,
      '#default_value' => $config->get('hostname'),
      '#description' => $this->t("Include the hostnamhostnamect Look details at
        <a href='@url' rel='nofollow' target='_new'>documentation</a>.",
        ['@url' => 'https://ipstack.com/documentation#hostname']
      ),
    ];

    $form['security'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the Security module'),
      '#required' => FALSE,
      '#default_value' => $config->get('security'),
      '#description' => $this->t("Assess risks and threats originating. Look details at
        <a href='@url' rel='nofollow' target='_new'>documentation</a>.",
        ['@url' => 'https://ipstack.com/documentation#security']
      ),
    ];

    $languages = [
      'en' => $this->t('English/US'),
      'de' => $this->t('German'),
      'es' => $this->t('Spanish'),
      'fr ' => $this->t('French'),
      'ja' => $this->t('Japanese'),
      'pt-br' => $this->t('Portugues (Brazil)'),
      'ru' => $this->t('Russian'),
      'zh' => $this->t('Chinese'),
    ];
    $language_default = $config->get('language');
    if (empty($language_default)) {
      $language_default = 0;
    }
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Specify Response Language'),
      '#required' => FALSE,
      '#multiple' => FALSE,
      '#options' => [$this->t('Default (English/Us)')] + $languages,
      '#default_value' => $language_default,
      '#description' => $this->t("Delivering its result set in different languages. Look details at
        <a href='@url' rel='nofollow' target='_new'>documentation</a>.",
        ['@url' => 'https://ipstack.com/documentation#language']
      ),
    ];

    $formats = [
      'json' => 'JSON',
      'xml' => 'XML',
    ];
    $default_format = $config->get('output');
    if (empty($default_format)) {
      $default_format = 0;
    }
    $form['output'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#required' => FALSE,
      '#multiple' => FALSE,
      '#options' => [$this->t('Default (JSON)')] + $formats,
      '#default_value' => $default_format,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('ipstack.settings');
    $ip = $form_state->getValue('ip');
    $config->set('ip', $ip);

    // Build request options.
    $options = [];
    $options_keys = ['fields', 'hostname', 'security', 'language', 'output'];
    foreach ($options_keys as $key) {
      $value = $form_state->getValue($key);

      // Save the last value.
      $config->set($key, $value);

      if (!empty($value)) {
        if (is_array($value)) {
          $value = implode(',', $value);
        }
        $options[$key] = $value;
      }
    }
    $config->save();

    $ipstack = new Ipstack($ip, $options);
    $ipstack->showResult();
  }

}
