<?php

namespace Drupal\integro\Plugin\Integro\Operation;

use Drupal\integro\OperationInterface;

/**
 * @IntegroOperation(
 *   id = "rest",
 *   label = "REST",
 * )
 */
class RestOperation extends OperationBase implements OperationInterface {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $client = $this->configuration['connector']->getClient();
    // $auth = $client->auth($this->configuration['connector']->getClientConfiguration());

    $client_configuration = $client->getConfiguration();
    $client_configuration['operation'] = $this->configuration;
    $client->setConfiguration($client_configuration);
    $result = $client->request();

    return $result;
  }

}
