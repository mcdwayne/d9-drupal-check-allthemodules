<?php

namespace Drupal\jsonrpc\ParameterFactory;

use Drupal\jsonrpc\ParameterDefinitionInterface;
use Shaper\Util\Context;

/**
 * Class RawParameterFactory just returns the raw parameter.
 *
 * @package Drupal\jsonrpc\ParameterFactory
 */
class RawParameterFactory extends ParameterFactoryBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(ParameterDefinitionInterface $parameter_definition) {
    return $parameter_definition->getSchema();
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputValidator() {
    // The input is the same as the output.
    return $this->getInputValidator();
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context = NULL) {
    return $data;
  }

}
