<?php

namespace Drupal\jsonrpc\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\jsonrpc\ExecutableWithParamsInterface;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\MethodInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation for JSON RPC methods.
 */
abstract class JsonRpcMethodBase extends PluginBase implements ContainerFactoryPluginInterface, ExecutableWithParamsInterface {

  /**
   * The RPC request for the current invocation.
   *
   * @var \Drupal\jsonrpc\Object\Request
   */
  private $rpcRequest;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, MethodInterface $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->rpcRequest = $configuration[HandlerInterface::JSONRPC_REQUEST_KEY];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * The RPC request for the current invocation.
   *
   * @return \Drupal\jsonrpc\Object\Request
   *   The request object.
   */
  protected function currentRequest() {
    return $this->rpcRequest;
  }

  /**
   * The RPC method definition for the current invocation.
   *
   * @return \Drupal\jsonrpc\MethodInterface
   *   The method definitionm.
   */
  protected function methodDefinition() {
    return $this->getPluginDefinition();
  }

  /**
   * Provides the schema that describes the results of the RPC method.
   *
   * Use NULL if the method does not provide results (is a notification).
   *
   * @return null|array
   *   The JSON Schema or a null in case of a notification.
   */
  abstract public static function outputSchema();

}
