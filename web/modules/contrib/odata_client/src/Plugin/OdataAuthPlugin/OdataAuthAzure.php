<?php

namespace Drupal\odata_client\Plugin\OdataAuthPlugin;

use Drupal\odata_client\Plugin\OdataAuthPluginBase;
use Drupal\odata_client\Entity\OdataServerInterface;
use Drupal\Core\DependencyInjection\Container;
use TheNetworg\OAuth2\Client\Provider\Azure;

/**
 * Class Azure.
 *
 * @package Drupal\odata_client\Plugin\OdataAuthPlugin
 *
 * @OdataAuthPlugin(
 *   id = "azure",
 *   label = @Translation("Azure"),
 * )
 */
class OdataAuthAzure extends OdataAuthPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAccessToken(OdataServerInterface $config,
    Container $serviceContainer) {
    $provider = new Azure([
      'clientId' => $config->getClientId(),
      'clientSecret' => $config->getClientSecret(),
      'redirectUri' => $config->getRedirectUri(),
      'scope' => 'openid',
      'resource' => $config->getUrlResource(),
      'tenant' => $config->getTenant(),
    ]);

    try {
      $access_token = $provider->getAccessToken('client_credentials');
      return $access_token;
    }
    catch (\Throwable $t) {
      $serviceContainer->get('logger.factory')
        ->get('odata_client')
        ->warning($t->getMessage());
    }

    return NULL;
  }

}
