<?php

namespace Drupal\gclient_storage\Plugin\Integro\Operation;

use Drupal\Component\Serialization\Json;
use Drupal\integro\Plugin\Integro\Operation\NativeOperation;
use Google_Service_Storage;

/**
 * @IntegroOperation(
 *   id = "gclient_storage_operation",
 *   label = "GClient Storage operation",
 * )
 */
class GclientStorageOperation extends NativeOperation {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $client = $this->configuration['connector']->getClient();
    $auth = $client->auth($this->configuration['connector']->getClientConfiguration());
    $storage_service = new Google_Service_Storage($auth['client']);

    list($resource_name, $method_name) = explode('.', $this->configuration['id']);
    $arguments = $this->configuration['arguments'];

    try {
      $result = call_user_func_array([$storage_service->$resource_name, $method_name], $arguments);
    }
    catch (\Exception $e) {
      watchdog_exception('gclient_storage', $e);
      return FALSE;
    }

    return $result;
  }

}
