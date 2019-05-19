<?php

namespace Drupal\streamy_aws\Plugin\StreamyCDN;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\streamy\StreamyCDNBase;
use Drupal\streamy\StreamyFormTrait;

/**
 * Provides a 'StreamyCDN' plugin.
 *
 * @StreamyCDN(
 *   id = "awscdn",
 *   name = @Translation("Aws CDN"),
 *   configPrefix = "streamy_aws",
 *   description = @Translation("Provides an Aws CDN.")
 * )
 */
class AwsCdn extends StreamyCDNBase {

  use StreamyFormTrait;

  /**
   * @inheritdoc
   */
  public function getExternalUrl($uri, string $scheme) {
    $url = NULL;
    $settings = $this->getPluginSettings($scheme);
    $cdnDomain = $settings['settings']['url'];
    $httpsOnly = (bool) $settings['settings']['https'];

    if ($cdnDomain &&
        (!$httpsOnly || ($httpsOnly && !$this->request->isSecure()))
    ) {
      $domain = Html::escape(UrlHelper::stripDangerousProtocols($cdnDomain));
      if (!$domain) {
        $this->logger->error('AWS CDN is enabled but no Domain Name has been set yet.');
        return FALSE;
      }

      // If domain is set to a root-relative path, add the hostname back in.
      if (strpos($domain, '/') === 0) {
        $domain = $this->request->getHttpHost() . $domain;
      }

      $scheme = $this->request->isSecure() ? 'https' : 'http';
      $cdnDomain = "{$scheme}://{$domain}";
      $url = "{$cdnDomain}/{$uri}";
    }
    return $url;
  }

  /**
   * @inheritdoc
   */
  public function getPluginSettings(string $scheme, array $config = []) {
    $pluginConfig = $config ? $config : (array) $this->config->get('plugin_configuration');
    return [
      'type'     => 'awscdn',
      'settings' => [
        'enabled' => $this->getPluginConfigurationSingleValue('enabled', $scheme, $pluginConfig),
        'url'     => $this->getPluginConfigurationSingleValue('url', $scheme, $pluginConfig),
        'https'   => $this->getPluginConfigurationSingleValue('https', $scheme, $pluginConfig),
      ],
    ];
  }

  /**
   * @inheritdoc
   */
  public function ensure(string $scheme, array $config = []) {
    // todo Validate URL!
    $settings = $this->getPluginSettings($scheme, $config);
    return (int) $settings['settings']['enabled'] === 1 ? !empty($settings['settings']['url']) : FALSE;
  }

}
