<?php

namespace Drupal\jsonrpc\Shaper;

use Drupal\Component\Serialization\Json;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\HandlerInterface;
use Drupal\jsonrpc\Object\Error;
use Drupal\jsonrpc\Object\ParameterBag;
use Drupal\jsonrpc\Object\Request;
use Drupal\jsonrpc\ParameterFactory\RawParameterFactory;
use Drupal\jsonrpc\ParameterDefinitionInterface;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Shaper\Transformation\TransformationBase;
use Shaper\Util\Context;
use Shaper\Validator\CollectionOfValidators;
use Shaper\Validator\InstanceofValidator;
use Shaper\Validator\JsonSchemaValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates RPC Request objects.
 */
class RpcRequestFactory extends TransformationBase {

  const REQUEST_ID_KEY = 'jsonrpc_request_id';

  const REQUEST_VERSION_KEY = 'jsonrpc_request_version';

  const REQUEST_IS_BATCH_REQUEST = 'jsonrpc_request_is_batch_request';

  /**
   * The JSON-RPC handler.
   *
   * @var \Drupal\jsonrpc\HandlerInterface
   */
  protected $handler;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The JSON Schema validator instance.
   *
   * @var \JsonSchema\Validator
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  public function __construct(HandlerInterface $handler, ContainerInterface $container, Validator $validator) {
    $this->handler = $handler;
    $this->container = $container;
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputValidator() {
    $schema = Json::decode(file_get_contents(__DIR__ . '/request-schema.json'));
    return new JsonSchemaValidator($schema, $this->validator, Constraint::CHECK_MODE_TYPE_CAST);
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputValidator() {
    return new CollectionOfValidators(new InstanceofValidator(Request::class));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  protected function doTransform($data, Context $context) {
    $context[static::REQUEST_IS_BATCH_REQUEST] = $this->isBatchRequest($data);
    // Treat everything as a batch of requests for uniformity.
    $data = $this->isBatchRequest($data) ? $data : [$data];
    return array_map(function ($item) use ($context) {
      return $this->denormalizeRequest($item, $context);
    }, $data);
  }

  /**
   * Denormalizes a single JSON-RPC request object.
   *
   * @param object $data
   *   The decoded JSON-RPC request to be denormalized.
   * @param \Shaper\Util\Context $context
   *   The denormalized JSON-RPC request.
   *
   * @return \Drupal\jsonrpc\Object\Request
   *   The JSON-RPC request.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  protected function denormalizeRequest($data, Context $context) {
    $id = isset($data['id']) ? $data['id'] : FALSE;
    $context[static::REQUEST_ID_KEY] = $id;
    $context[static::REQUEST_VERSION_KEY] = $this->handler->supportedVersion();
    $batch = $context[static::REQUEST_IS_BATCH_REQUEST];
    $params = $this->denormalizeParams($data, $context);
    return new Request($data['jsonrpc'], $data['method'], $batch, $id, $params);
  }

  /**
   * Denormalizes a JSON-RPC request object's parameters.
   *
   * @param object $data
   *   The decoded JSON-RPC request to be denormalized.
   * @param \Shaper\Util\Context $context
   *   The denormalized JSON-RPC request.
   *
   * @return \Drupal\jsonrpc\Object\ParameterBag|null
   *   The denormalized parameters or NULL if none were provided.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  protected function denormalizeParams($data, Context $context) {
    if (!$this->handler->supportsMethod($data['method'])) {
      throw $this->newException(Error::methodNotFound($data['method']), $context);
    }
    $method = $this->handler->getMethod($data['method']);
    $params = $method->getParams();
    if (is_null($params)) {
      if (isset($data['params'])) {
        $error = Error::invalidParams("The ${data['method']} method does not accept parameters.");
        throw $this->newException($error, $context);
      }
      return NULL;
    }
    $arguments = [];
    $positional = $method->areParamsPositional();
    foreach ($params as $key => $param) {
      if (isset($data['params'][$key])) {
        $arguments[$key] = $this->denormalizeParam($data['params'][$key], $param);
      }
      // Only force the presence of required parameters.
      elseif ($param->isRequired()) {
        throw $this->newException(Error::invalidParams("Missing required parameter: $key"), $context);
      }
    }
    return new ParameterBag($arguments, $positional);
  }

  /**
   * Denormalizes a single JSON-RPC request object parameter.
   *
   * @param mixed $argument
   *   The decoded JSON-RPC request parameter to be denormalized.
   * @param \Drupal\jsonrpc\ParameterDefinitionInterface $parameter_definition
   *   The JSON-RPC request's parameter definition.
   *
   * @return mixed
   *   The denormalized parameter.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  protected function denormalizeParam($argument, ParameterDefinitionInterface $parameter_definition) {
    $factory_class = $parameter_definition->getFactory() ?: RawParameterFactory::class;
    $factory = call_user_func_array(
      [$factory_class, 'create'],
      [$parameter_definition, $this->container]
    );
    $context = new Context([
      ParameterDefinitionInterface::class => $parameter_definition,
    ]);
    try {
      // TODO: Wrap other shaper transformations in a similar way for nicer
      // error outputs.
      return $factory->transform($argument, $context);
    }
    catch (\TypeError $exception) {
      $message = "The {$parameter_definition->getId()} parameter does not conform to the parameter schema. {$exception->getMessage()}";
      throw JsonRpcException::fromError(Error::invalidParams($message));
    }
  }

  /**
   * Determine if the request is a batch request.
   *
   * @param array $data
   *   The raw HTTP request data.
   *
   * @return bool
   *   Whether the HTTP request contains more than one RPC request.
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   *   Thrown if the request contains RPC requests without a 'jsonrpc' member.
   */
  protected function isBatchRequest(array $data) {
    if (isset($data['jsonrpc'])) {
      return FALSE;
    }
    $supported_version = $this->handler->supportedVersion();
    $filter = function ($version) use ($supported_version) {
      return $version === $supported_version;
    };
    if (count(array_filter(array_column($data, 'jsonrpc'), $filter)) === count($data)) {
      return TRUE;
    }
    throw JsonRpcException::fromError(Error::invalidRequest("Every request must include a 'jsonrpc' member with a value of $supported_version."));
  }

  /**
   * Helper for creating an error RPC response exception.
   *
   * @param \Drupal\jsonrpc\Object\Error $error
   *   The JSON-RPC Error.
   * @param \Shaper\Util\Context $context
   *   The JSON-RPC request context.
   *
   * @return \Drupal\jsonrpc\Exception\JsonRpcException
   *   The new exception object.
   */
  protected function newException(Error $error, Context $context) {
    return JsonRpcException::fromError($error, $context[static::REQUEST_ID_KEY], $context[static::REQUEST_VERSION_KEY]);
  }

}
