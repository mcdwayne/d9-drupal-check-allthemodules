<?php

namespace Drupal\nginx\Service;

use Drupal\idna\Service\IdnaConvertInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class NginxSite.
 */
class NginxSite implements NginxSiteInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config factory.
   *
   * @var \Drupal\idna\Service\IdnaConvertInterface
   */
  protected $idna;

  /**
   * Creates a new NginxSite Service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\idna\Service\IdnaConvertInterface $idna
   *   Idna Convert.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
      IdnaConvertInterface $idna) {
    // Services.
    $config = $config_factory->get('letsencrypt.settings');
    $this->configFactory = $config_factory;
    $this->idna = $idna;
    $this->config = $config;
  }

  /**
   * Config get.
   */
  public function get(array $domains) {
    $name = $this->clearDomains($domains);
    $no1 = array_shift($domains);
    $dkey = \Drupal::transliteration()->transliterate($no1);
    $root = "/var/www/$dkey";
    $log = "/var/log/$dkey";
    $run = "/var/run";
    $nginx = "/etc/nginx/vhosts/$dkey";
    $site = [
      'key' => $dkey,
      'name' => $name,
      // Mode: php | proxy.
      'mode' => 'php',
      'proxy' => FALSE,
      // Https: FALSE | TRUE | force.
      'https' => 'force',
      'vhosts' => "$nginx/{$dkey}",
      'root' => $root,
      'log' => $log,
      'php' => "$run/php/php-fpm.sock",
      'fullchain' => "$nginx/fullchain.pem",
      'private' => "$nginx/private.pem",
      'includes' => [
        'includes/letsencrypt.conf',
        'includes/common.conf',
        'includes/drupal-8.conf',
      ],
    ];
    return $this->getConfig($site);
  }

  /**
   * Config get.
   */
  public function getConfig(array $site) {
    $result = [
      'http' => $this->render($site),
      'https' => $this->render($site, TRUE),
    ];
    return $result;
  }

  /**
   * Suspend Config get.
   */
  public function render($site, $https = FALSE) {
    if (!$site["https"] && $https) {
      return FALSE;
    }
    $renderable = [
      '#theme' => 'ngxin-site',
      '#site' => $site,
      '#https' => $https,
    ];
    $html = \Drupal::service('renderer')->renderRoot($renderable);
    $html = preg_replace('/<!--(.*)-->/Uis', '', $html);
    $html = trim($html) . "\n";
    return $html;
  }

  /**
   * Suspend Config get.
   */
  public function clearDomains($domains, $implode = TRUE) {
    $result = [];
    foreach ($domains as $key => $domain) {
      $domain = trim($domain);
      $domain = strtolower($domain);
      $domain = strstr("{$domain};", ';', TRUE);
      $domain = strstr("{$domain} ", ' ', TRUE);
      $domain = preg_replace('/\-+/', '-', $domain);
      $domain = $this->idna->encode($domain);
      $domain = \Drupal::transliteration()->transliterate($domain);
      $domain = preg_replace('/[^a-zA-Z0-9-.]+/is', '', $domain);
      if (strlen($domain) > 3) {
        $result[$domain] = $domain;
      }
    }
    if ($implode) {
      return implode(" ", $result);
    }
    return $result;
  }

  /**
   * Suspend Config get.
   */
  public function suspend() {
    $renderable = [
      '#theme' => 'ngxin-suspend',
      '#site' => [
        'vhosts' => '/etc/nginx/vhosts',
        'include' => 'include /etc/nginx/default/default.conf;',
      ],
    ];
    $config = \Drupal::service('renderer')->renderRoot($renderable);
    return $config;
  }

}
