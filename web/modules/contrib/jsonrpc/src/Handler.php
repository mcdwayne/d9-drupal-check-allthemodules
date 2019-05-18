<?php

namespace Drupal\jsonrpc;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\Object\Error;
use Drupal\jsonrpc\Object\ParameterBag;
use Drupal\jsonrpc\Object\Request;
use Drupal\jsonrpc\Object\Response;

/**
 * Manages all the JSON-RPC business logic.
 */
class Handler implements HandlerInterface {

  /**
   * The support JSON-RPC version.
   *
   * @var string
   */
  const SUPPORTED_VERSION = '2.0';

  /**
   * The JSON-RPC method plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $methodManager;

  /**
   * Handler constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $method_manager
   *   The plugin manager for the JSON RPC methods.
   */
  public function __construct(PluginManagerInterface $method_manager) {
    $this->methodManager = $method_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportedVersion() {
    return static::SUPPORTED_VERSION;
  }

  /**
   * {@inheritdoc}
   */
  public function batch(array $requests) {
    return array_filter(array_map(function (Request $request) {
      return $this->doRequest($request);
    }, $requests));
  }

  /**
   * {@inheritdoc}
   */
  public function supportedMethods() {
    return $this->methodManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMethod($name) {
    return !is_null($this->getMethod($name));
  }

  /**
   * {@inheritdoc}
   */
  public function availableMethods(AccountInterface $account = NULL) {
    return array_filter($this->supportedMethods(), function (MethodInterface $method) {
      return $method->access('execute');
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getMethod($name) {
    return $this->methodManager->getDefinition($name, FALSE);
  }

  /**
   * Executes an RPC call and returns a JSON-RPC response.
   *
   * @param \Drupal\jsonrpc\Object\Request $request
   *   The JSON-RPC request.
   *
   * @return \Drupal\jsonrpc\Object\Response|null
   *   The JSON-RPC response.
   */
  protected function doRequest(Request $request) {
    // Helper closure to handle eventual exceptions.
    $handle_exception = function ($e, Request $request) {
      if (!$e instanceof JsonRpcException) {
        $id = $request->isNotification() ? FALSE : $request->id();
        $e = JsonRpcException::fromPrevious($e, $id);
      }
      return $e->getResponse();
    };
    try {
      $result = $this->doExecution($request);
      if ($request->isNotification()) {
        return NULL;
      }
      $rpc_response = $result instanceof Response
        ? $result
        : new Response(static::SUPPORTED_VERSION, $request->id(), $result);
      $methodPluginClass = $this->getMethod($request->getMethod())->getClass();
      $result_schema = call_user_func([$methodPluginClass, 'outputSchema']);
      $rpc_response->setResultSchema($result_schema);
      return $rpc_response;
    }
    // Catching Throwable allows us to recover from more kinds of exceptions
    // that might occur in badly written 3rd party code.
    catch (\Throwable $e) {
      return $handle_exception($e, $request);
    }
    // @TODO: Remove the following when PHP7 is the minimum supported version.
    catch (\Exception $e) {
      return $handle_exception($e, $request);
    }
  }

  /**
   * Gets an anonymous function which executes the RPC method.
   *
   * @param \Drupal\jsonrpc\Object\Request $request
   *   The JSON-RPC request.
   *
   * @return \Drupal\jsonrpc\Object\Response|null
   *   The JSON-RPC response.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  protected function doExecution(Request $request) {
    if ($method = $this->getMethod($request->getMethod())) {
      $this->checkAccess($method);
      $configuration = [HandlerInterface::JSONRPC_REQUEST_KEY => $request];
      $executable = $this->getExecutable($method, $configuration);
      return $request->hasParams()
        ? $executable->execute($request->getParams())
        : $executable->execute(new ParameterBag([]));
    }
    else {
      throw JsonRpcException::fromError(Error::methodNotFound($method->id()));
    }
  }

  /**
   * Gets an executable instance of an RPC method.
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The method definition.
   * @param array $configuration
   *   Method configuration.
   *
   * @return object
   *   The executable method.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   *   In case of error.
   */
  protected function getExecutable(MethodInterface $method, array $configuration) {
    try {
      return $this->methodManager->createInstance($method->id(), $configuration);
    }
    catch (PluginException $e) {
      throw JsonRpcException::fromError(Error::methodNotFound($method->id()));
    }
  }

  /**
   * Check execution access.
   *
   * @param \Drupal\jsonrpc\MethodInterface $method
   *   The method for which to check access.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  protected function checkAccess(MethodInterface $method) {
    // TODO: Add cacheability metadata here.
    /* @var \Drupal\jsonrpc\MethodInterface $method_definition */
    $access_result = $method->access('execute', NULL, TRUE);
    if (!$access_result->isAllowed()) {
      $reason = 'Access Denied';
      if ($access_result instanceof AccessResultReasonInterface && ($detail = $access_result->getReason())) {
        $reason .= ': ' . $detail;
      }
      throw JsonRpcException::fromError(Error::invalidRequest($reason, $access_result));
    }
  }

}
