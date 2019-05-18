<?php

namespace Drupal\odata_client\Plugin\OdataAuthPlugin;

use Drupal\odata_client\Plugin\OdataAuthPluginBase;
use Drupal\odata_client\Entity\OdataServerInterface;
use Drupal\Core\DependencyInjection\Container;
use League\OAuth2\Client\Provider\GenericProvider;

/**
 * Class OdataAuthGeneric.
 *
 * @package Drupal\odata_client\Plugin\OdataAuthPlugin
 *
 * @OdataAuthPlugin(
 *   id = "generic",
 *   label = @Translation("Generic"),
 * )
 */
class OdataAuthGeneric extends OdataAuthPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getAccessToken(OdataServerInterface $config,
    Container $serviceContainer) {
    $provider = new GenericProvider([
      'clientId' => $config->getClientId(),
      'clientSecret' => $config->getClientSecret(),
      'redirectUri' => $config->getRedirectUri(),
      'urlAuthorize' => $config->getUrlAuthorize(),
      'urlAccessToken' => $config->getUrlToken(),
      'urlResourceOwnerDetails' => $config->getUrlResource(),
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
