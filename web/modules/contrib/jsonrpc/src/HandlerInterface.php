<?php

namespace Drupal\jsonrpc;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for the handler.
 */
interface HandlerInterface {

  /**
   * The configuration array key for the JSON-RPC request object.
   *
   * @var string
   */
  const JSONRPC_REQUEST_KEY = 'jsonrpc_request';

  /**
   * Executes a batch of remote procedure calls.
   *
   * @param \Drupal\jsonrpc\Object\Request[] $requests
   *   The JSON-RPC requests.
   *
   * @return array
   *   The JSON-RPC responses, if any. Notifications are not returned.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  public function batch(array $requests);

  /**
   * Gets a method definition by method name.
   *
   * @param string $name
   *   The method name for which support should be determined.
   *
   * @return \Drupal\jsonrpc\MethodInterface|null
   *   The method definition.
   */
  public function getMethod($name);

  /**
   * The methods which are available to the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional). The account for which to get available methods. Defaults to
   *   the current user.
   *
   * @return \Drupal\jsonrpc\MethodInterface[]
   *   The methods.
   */
  public function availableMethods(AccountInterface $account = NULL);

  /**
   * The methods supported by the handler.
   *
   * @return \Drupal\jsonrpc\MethodInterface[]
   *   The methods.
   */
  public function supportedMethods();

  /**
   * Whether the given method is supported.
   *
   * @param string $name
   *   The method name for which support should be determined.
   *
   * @return bool
   *   Whether the handler supports the given method name.
   */
  public function supportsMethod($name);

  /**
   * The supported JSON-RPC version.
   *
   * @return string
   *   The version.
   */
  public static function supportedVersion();

}
