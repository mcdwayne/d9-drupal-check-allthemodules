<?php

namespace Drupal\gapi\Plugin;

use Drupal\Core\Plugin\PluginBase;
use \Google_Client;

abstract class GoogleApiServiceProviderBase extends PluginBase implements GoogleApiServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  abstract public function getService(Google_Client $client);

}
