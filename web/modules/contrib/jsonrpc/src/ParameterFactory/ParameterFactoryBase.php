<?php

namespace Drupal\jsonrpc\ParameterFactory;

use Drupal\jsonrpc\ParameterDefinitionInterface;
use Drupal\jsonrpc\ParameterFactoryInterface;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use Shaper\Transformation\TransformationBase;
use Shaper\Validator\JsonSchemaValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for parameter factories.
 */
abstract class ParameterFactoryBase extends TransformationBase implements ParameterFactoryInterface {

  /**
   * The schema validator to ensure the input data adheres to the expectation.
   *
   * @var \JsonSchema\Validator
   */
  protected $validator;

  /**
   * The validation class for shaper interactions.
   *
   * @var \Shaper\Validator\ValidateableInterface
   */
  protected $inputValidator;

  /**
   * The parameter definition.
   *
   * @var \Drupal\jsonrpc\ParameterDefinitionInterface
   */
  protected $definition;

  /**
   * ParameterFactoryBase constructor.
   *
   * @param \Drupal\jsonrpc\ParameterDefinitionInterface $definition
   *   The parameter definition.
   * @param \JsonSchema\Validator $validator
   *   The JSON Schema validation object.
   */
  public function __construct(ParameterDefinitionInterface $definition, Validator $validator) {
    $this->validator = $validator;
    $this->definition = $definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ParameterDefinitionInterface $definition, ContainerInterface $container) {
    return new static($definition, $container->get('jsonrpc.schema_validator'));
  }

  /**
   * {@inheritdoc}
   */
  public function getInputValidator() {
    if (!$this->inputValidator) {
      $schema = $this->definition->getSchema();
      $this->inputValidator = new JsonSchemaValidator(
        $schema,
        $this->validator,
        Constraint::CHECK_MODE_TYPE_CAST
      );
    }
    return $this->inputValidator;
  }

}
