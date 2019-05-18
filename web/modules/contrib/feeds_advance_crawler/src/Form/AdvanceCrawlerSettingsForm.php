<?php

namespace Drupal\feeds_advance_crawler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class AdvanceCrawlerSettingsForm.
 */
class AdvanceCrawlerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'feeds_advance_crawler.settings',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advance_crawler_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('feeds_advance_crawler.settings');

    $form['nodejs_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node.js server host'),
      '#default_value' => $config->get('nodejs_host'),
      '#size' => 40,
      '#required' => TRUE,
      '#description' => $this->t('The hostname of the Node.js server for Advance Feeds Crawler.'),
    ];

    $form['nodejs_port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Node.js server port'),
      '#default_value' => $config->get('nodejs_port'),
      '#size' => 10,
      '#required' => TRUE,
      '#description' => $this->t('The port of the Node.js server.'),
    ];

    $form['proxy'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Proxy URL'),
      '#default_value' => $config->get('proxy'),
      '#size' => 60,
      '#description' => $this->t('Complete Proxy URL. Ex- http://192.*.*.*:8080'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    $host = $values['nodejs_host'];
    $port = $values['nodejs_port'];
    $proxy = $values['proxy'];
    $this->checkServer($host, $port);
    if (!empty($proxy)) {
      $this->checkProxy($proxy);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('feeds_advance_crawler.settings')
      ->set('nodejs_host', $values['nodejs_host'])
      ->set('nodejs_port', $values['nodejs_port'])
      ->set('proxy', $values['proxy'])
      ->save();
    parent::submitForm($form, $form_state);
  }

  public function checkServer(string $host, string $port) {
    $client = new Client();
    $url = $host . ':' . $port;

    try {
      $response = $client->get($url);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      $this->messenger()->addWarning($this->t('This %site seems to be broken because of error "%error". Please configure the Nodejs server if you haven\'t configured yet.', $args));
    }
  }

  public function checkProxy(string $proxy) {
    $client = new Client();
    $url = 'https://google.com';

    try {
      $response = $client->get($url, ['proxy' => $proxy]);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      $this->messenger()->addWarning($this->t('This %site seems to be broken because of error "%error". Please configure the proxy correctly.', $args));
    }
  }
}
