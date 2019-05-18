<?php

namespace Drupal\ipstack;

/**
 * @file
 * Contains \Drupal\ipstack\Ipstack.
 */

use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Render\Markup;

/**
 * Class Ipstack.
 *
 * Provides ipstack.com API.
 *
 * @ingroup ipstack
 */
class Ipstack {

  use StringTranslationTrait;

  /**
   * IP address.
   *
   * @var string
   */
  protected $ip;

  /**
   * Ipstack options.
   *
   * @var array
   */
  protected $options;

  /**
   * Ipstack constructor.
   *
   * @param string $ip
   *   IP address.
   * @param array $options
   *   Ipstack options.
   */
  public function __construct($ip = '', array $options = [], TranslationInterface $string_translation = NULL) {
    $this->setIp($ip);
    $this->setOptions($options);
    $this->stringTranslation = $string_translation;
    if (!$this->stringTranslation) {
      $this->stringTranslation = \Drupal::service('string_translation');
    }
  }

  /**
   * Set IP address.
   *
   * @param string $ip
   *   IP address.
   */
  public function setIp($ip) {
    $this->ip = trim($ip);

    // If IP is empty set current user IP.
    if (empty($this->ip)) {
      $this->ip = \Drupal::request()->getClientIp();
    }
  }

  /**
   * Set ipstack options. (config value if empty).
   *
   * @param string $options
   *   Ipstack options.
   */
  public function setOptions($options) {
    if (empty($options['access_key'])) {
      $access_key = \Drupal::config('ipstack.settings')->get('access_key');
      $options['access_key'] = $access_key;
    }
    $this->options = $options;
  }

  /**
   * Get ipstack URL.
   *
   * @return string
   *   Ipstack URL for data retrieving.
   */
  public function getUrl() {
    $prot = !empty(\Drupal::config('ipstack.settings')->get('use_https')) ? 'https' : 'http';
    $url_options = ['absolute' => TRUE, 'query' => $this->options];
    $url = Url::fromUri($prot . "://api.ipstack.com/" . $this->ip, $url_options);
    return $url->toString();
  }

  /**
   * Get ipstack data.
   *
   * @return object
   *   Ipstack data.
   */
  public function getData() {
    // Access Key is required.
    if (empty($this->options['access_key'])) {
      return FALSE;
    }

    // Get data from api.ipstack.com .
    $client = \Drupal::httpClient();
    $responce = $client->get($this->getUrl());
    $status = $responce->getStatusCode();
    if ($status != 200) {
      return FALSE;
    }
    $data = json_decode($responce->getBody());
    return $data;
  }

  /**
   * Show result.
   *
   */
  public function showResult() {
    $url = $this->getUrl();
    $data = $this->getData();

    if ($data) {
      $msg = $this->t("Request: <a href='@url' target='_new'>@url</a>", ['@url' => $url]);
      drupal_set_message($msg);

      $status = 'status';
      if (!empty($data->error)) {
        $data = $data->error;
        $status = 'error';
      }
      $data_msg = $this->t('Responce:') . ' <pre>' . print_r($data, 1) . '</pre>';
      $msg = Markup::create($data_msg);
      drupal_set_message($msg, $status);
    }
    else {
      $msg = $this->t('No data');
      drupal_set_message($msg, 'error');
    }
  }

}
