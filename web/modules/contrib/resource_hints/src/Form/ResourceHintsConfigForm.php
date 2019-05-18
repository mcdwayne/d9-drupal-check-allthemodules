<?php

namespace Drupal\resource_hints\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure resource hints for this site.
 */
class ResourceHintsConfigForm extends ConfigFormBase {

  const OUTPUT_LINK_HEADER = 0;
  const OUTPUT_LINK_ELEMENT = 1;
  const DNS_PREFETCH_ENABLED = 'on';
  const DNS_PREFETCH_DISABLED = 'off';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'resource_hints_admin_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'resource_hints.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('resource_hints.settings');

    $form['dns_prefetch'] = [
      '#type' => 'details',
      '#title' => $this->t('DNS Prefetch'),
      '#open' => TRUE,
    ];

    $form['dns_prefetch']['dns_prefetch_output'] = [
      '#type' => 'select',
      '#title' => $this->t('Output type'),
      '#options' => [
        self::OUTPUT_LINK_HEADER => $this->t('Link Header'),
        self::OUTPUT_LINK_ELEMENT => $this->t('Link Element'),
      ],
      '#default_value' => $config->get('dns_prefetch_output'),
      '#description' => $this->t('Resource hints can be output as an HTTP Link header or HTML link element'),
    ];

    $form['dns_prefetch']['dns_prefetch_resources'] = [
      '#type' => 'textarea',
      '#default_value' => implode(PHP_EOL, $config->get('dns_prefetch_resources')),
      '#title' => $this->t('Resources'),
      '#description' => $this->t('The DNS resources you wish to be prefetched. Enter one resource per line.'),
    ];

    $form['dns_prefetch']['dns_prefetch_control'] = [
      '#type' => 'select',
      '#title' => $this->t('DNS Prefetch Control'),
      '#options' => [
        self::DNS_PREFETCH_ENABLED => $this->t('Enabled'),
        self::DNS_PREFETCH_DISABLED => $this->t('Disabled'),
      ],
      '#default_value' => $config->get('dns_prefetch_control'),
      '#description' => $this->t('By default browsers will not use DNS prefetching when a page is served via HTTPS, you must explicitly enable prefetching for HTTPS. Disabling prefetching will prevent browsers using prefetching and any inline attempts to enable it will be ignored.'),
    ];

    $form['preconnect'] = [
      '#type' => 'details',
      '#title' => $this->t('Preconnect'),
      '#open' => TRUE,
    ];

    $form['preconnect']['preconnect_output'] = [
      '#type' => 'select',
      '#title' => $this->t('Output type'),
      '#options' => [
        self::OUTPUT_LINK_HEADER => $this->t('Link Header'),
        self::OUTPUT_LINK_ELEMENT => $this->t('Link Element'),
      ],
      '#default_value' => $config->get('preconnect_output'),
      '#description' => $this->t('Resource hints can be output as an HTTP Link header or HTML link element'),
    ];

    $form['preconnect']['preconnect_resources'] = [
      '#type' => 'textarea',
      '#default_value' => implode(PHP_EOL, $config->get('preconnect_resources')),
      '#title' => $this->t('Resources'),
      '#description' => $this->t('The resources you wish to be preconnected. Enter one resource per line.'),
    ];

    $form['prefetch'] = [
      '#type' => 'details',
      '#title' => $this->t('Prefetch'),
      '#open' => TRUE,
    ];

    $form['prefetch']['prefetch_output'] = [
      '#type' => 'select',
      '#title' => $this->t('Output type'),
      '#options' => [
        self::OUTPUT_LINK_HEADER => $this->t('Link Header'),
        self::OUTPUT_LINK_ELEMENT => $this->t('Link Element'),
      ],
      '#default_value' => $config->get('prefetch_output'),
      '#description' => $this->t('Resource hints can be output as an HTTP Link header or HTML link element'),
    ];

    $form['prefetch']['prefetch_resources'] = [
      '#type' => 'textarea',
      '#default_value' => implode(PHP_EOL, $config->get('prefetch_resources')),
      '#title' => $this->t('Resources'),
      '#description' => $this->t('The resources you wish to be prefetched. Enter one resource per line.'),
    ];

    $form['prerender'] = [
      '#type' => 'details',
      '#title' => $this->t('Prerender'),
      '#open' => TRUE,
    ];

    $form['prerender']['prerender_output'] = [
      '#type' => 'select',
      '#title' => $this->t('Output type'),
      '#options' => [
        self::OUTPUT_LINK_HEADER => $this->t('Link Header'),
        self::OUTPUT_LINK_ELEMENT => $this->t('Link Element'),
      ],
      '#default_value' => $config->get('prerender_output'),
      '#description' => $this->t('Resource hints can be output as an HTTP Link header or HTML link element'),
    ];

    $form['prerender']['prerender_resources'] = [
      '#type' => 'textarea',
      '#default_value' => implode(PHP_EOL, $config->get('prerender_resources')),
      '#title' => $this->t('Resources'),
      '#description' => $this->t('The resources you wish to be prerendered. Enter one resource per line.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $dns_prefetch_resources = explode(PHP_EOL, $form_state->getValue('dns_prefetch_resources'));
    $preconnect_resources = explode(PHP_EOL, $form_state->getValue('preconnect_resources'));
    $prefetch_resources = explode(PHP_EOL, $form_state->getValue('prefetch_resources'));
    $prerender_resources = explode(PHP_EOL, $form_state->getValue('prerender_resources'));
    $config = \Drupal::service('config.factory')->getEditable('resource_hints.settings');
    $config->set('dns_prefetch_resources', $dns_prefetch_resources)
      ->set('dns_prefetch_output', $form_state->getValue('dns_prefetch_output'))
      ->set('dns_prefetch_control', $form_state->getValue('dns_prefetch_control'))
      ->set('preconnect_resources', $preconnect_resources)
      ->set('preconnect_output', $form_state->getValue('preconnect_output'))
      ->set('prefetch_resources', $prefetch_resources)
      ->set('prefetch_output', $form_state->getValue('prefetch_output'))
      ->set('prerender_resources', $prerender_resources)
      ->set('prerender_output', $form_state->getValue('prerender_output'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
