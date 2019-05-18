<?php

namespace Drupal\jsonrpc\Shaper;

use Drupal\Component\Serialization\Json;
use Drupal\jsonrpc\Exception\JsonRpcException;
use Drupal\jsonrpc\Object\Response;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Shaper\Transformation\TransformationBase;
use Shaper\Util\Context;
use Shaper\Validator\AcceptValidator;
use Shaper\Validator\CollectionOfValidators;
use Shaper\Validator\InstanceofValidator;
use Shaper\Validator\JsonSchemaValidator;

/**
 * Creates RPC Response objects.
 */
class RpcResponseFactory extends TransformationBase {

  const RESPONSE_VERSION_KEY = RpcRequestFactory::REQUEST_VERSION_KEY;
  const REQUEST_IS_BATCH_REQUEST = RpcRequestFactory::REQUEST_IS_BATCH_REQUEST;

  /**
   * The JSON Schema validator.
   *
   * @var \JsonSchema\Validator
   */
  protected $validator;

  /**
   * The output validator, based on the JSON Schema.
   *
   * @var \Shaper\Validator\ValidateableInterface
   */
  protected $outputValidator;

  /**
   * RpcResponseFactory constructor.
   *
   * @param \JsonSchema\Validator $validator
   *   The JSON Schema validator.
   */
  public function __construct(Validator $validator) {
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputValidator() {
    return new CollectionOfValidators(new InstanceofValidator(Response::class));
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputValidator() {
    return $this->outputValidator
      ? $this->outputValidator
      : new AcceptValidator();
  }

  /**
   * Sets the schema for the response output.
   *
   * @param array|null $result_schema
   *   The array of the response.
   */
  public function setOutputSchema($result_schema) {
    $schema = Json::decode(file_get_contents(__DIR__ . '/response-schema.json'));
    $schema['properties']['result'] = $result_schema;
    $this->outputValidator = new JsonSchemaValidator(
      $schema,
      $this->validator,
      Constraint::CHECK_MODE_TYPE_CAST
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {
    $this->setOutputSchema($data[0]->getResultSchema());
    $output = array_map(function (Response $response) use ($context) {
      try {
        return $this->doNormalize($response, $context);
      }
      catch (\Exception $e) {
        return JsonRpcException::fromPrevious($e, $response->id(), $context[static::RESPONSE_VERSION_KEY]);
      }
    }, $data);
    return $context[static::REQUEST_IS_BATCH_REQUEST] ? $output : reset($output);
  }

  /**
   * Performs the actual normalization.
   *
   * @param \Drupal\jsonrpc\Object\Response $response
   *   The RPC Response object to return.
   * @param \Shaper\Util\Context $context
   *   The context object.
   *
   * @return array
   *   The normalized response.
   */
  protected function doNormalize(Response $response, Context $context) {
    $normalized = [
      'jsonrpc' => $context[static::RESPONSE_VERSION_KEY],
      'id' => $response->id(),
    ];
    if ($response->isResultResponse()) {
      $normalized['result'] = $response->getResult();
    }
    if ($response->isErrorResponse()) {
      $error = $response->getError();
      $normalized['error'] = [
        'code' => $error->getCode(),
        'message' => $error->getMessage(),
        'data' => $error->getData(),
      ];
    }
    return $normalized;
  }

}
