<?php

namespace Drupal\jsonrpc;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Core\Access\AccessibleInterface;

/**
 * Interface for the method plugins.
 */
interface MethodInterface extends AccessibleInterface, PluginDefinitionInterface {

  /**
   * The class method to call.
   *
   * @return string
   *   The PHP method on the RPC method object to call. Defaults to: execute.
   */
  public function call();

  /**
   * How to use this method.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The usage text for the method.
   */
  public function getUsage();

  /**
   * The parameters for this method.
   *
   * Can be a keyed array where the parameter names are the keys or an indexed
   * array for positional parameters.
   *
   * @return \Drupal\jsonrpc\MethodParameterInterface[]|null
   *   The method params or NULL if none are accepted.
   */
  public function getParams();

  /**
   * Whether the parameters are by-position.
   *
   * @return bool
   *   True if the parameters are positional.
   */
  public function areParamsPositional();

}
