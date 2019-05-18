<?php

namespace Drupal\jsonrpc\Annotation;

use Drupal\Component\Annotation\AnnotationBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonrpc\MethodInterface;

/**
 * Defines a JsonRpcParameterDefinition annotation object.
 *
 * @see \Drupal\jsonrpc\Plugin\JsonRpcServiceManager
 * @see plugin_api
 *
 * @Annotation
 */
class JsonRpcMethod extends AnnotationBase implements MethodInterface {

  /**
   * The access required to use this RPC method.
   *
   * @var mixed
   */
  public $access;

  /**
   * The class method to call.
   *
   * Optional. If the method ID is 'foo.bar', this defaults to 'bar'. If the
   * method ID does not contain a dot (.), defaults to 'execute'.
   *
   * @var string
   */
  public $call;

  /**
   * How to use this method.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $usage;

  /**
   * The parameters for this method.
   *
   * Can be a keyed array where the parameter names are the keys or an indexed
   * array for positional parameters.
   *
   * @var \Drupal\jsonrpc\Annotation\JsonRpcParameterDefinition[]
   */
  public $params = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->getId();
  }

  /**
   * {@inheritdoc}
   */
  public function call() {
    if (!isset($this->call)) {
      $this->call = 'execute';
    }
    return $this->call;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsage() {
    $this->usage;
  }

  /**
   * {@inheritdoc}
   */
  public function getParams() {
    return $this->params;
  }

  /**
   * {@inheritdoc}
   */
  public function areParamsPositional() {
    return array_reduce(array_keys($this->getParams()), function ($positional, $key) {
      return $positional ? !is_string($key) : $positional;
    }, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'execute', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $account ?: \Drupal::currentUser();
    switch ($operation) {
      case 'execute':
        if (is_callable($this->access)) {
          return call_user_func_array($this->access, [
            $operation,
            $account,
            $return_as_object,
          ]);
        }
        $access_result = AccessResult::allowed();
        foreach ($this->access as $permission) {
          $access_result = $access_result->andIf(AccessResult::allowedIfHasPermission($account, $permission));
        }
        break;

      case 'view':
        $access_result = $this->access('execute', $account, $return_as_object);
        break;

      default:
        $access_result = AccessResult::neutral();
        break;
    }
    return $return_as_object ? $access_result : $access_result->isAllowed();
  }

}
