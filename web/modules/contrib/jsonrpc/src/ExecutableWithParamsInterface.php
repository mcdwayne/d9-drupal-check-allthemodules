<?php

namespace Drupal\jsonrpc;

use Drupal\jsonrpc\Object\ParameterBag;

/**
 * Add to methods that can be executed with params.
 */
interface ExecutableWithParamsInterface {

  /**
   * Executes the action with the parameters passed in.
   *
   * @param \Drupal\jsonrpc\Object\ParameterBag $params
   *   The parameters.
   *
   * @return mixed
   *   The result of the execution.
   */
  public function execute(ParameterBag $params);

}
