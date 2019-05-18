<?php

namespace Drupal\integro\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Converts integration IDs in route parameters to integrations.
 *
 * To use it, add a `integro.integration` key to the route parameter's options.
 * Its value is as follows:
 * @code
 * example.route:
 *   path: foo/{bar}
 *   options:
 *     parameters:
 *       bar:
 *         integro.integration:
 *           # Whether the conversion is enabled. Boolean. Optional. Defaults
 *           # to TRUE.
 *           enabled: TRUE
 * @endcode
 *
 * To use the default behavior, its value is as follows:
 * @code
 * example.route:
 *   path: foo/{bar}
 *   options:
 *     parameters:
 *       bar:
 *         integro.integration: {}
 * @endcode
 */
class IntegrationConverter implements ParamConverterInterface {

  use IntegrationBasedConverterTrait;

  /**
   * {@inheritdoc}
   */
  public function doConvert($integration_id, array $converter_definition) {
    if ($this->integrationManager->hasIntegration($integration_id)) {
      return $this->integrationManager->getIntegration($integration_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConverterDefinitionKey() {
    return 'integro.integration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getConverterDefinitionConstraint() {
    return new Collection([
      'enabled' => new Optional(new Type('boolean')),
    ]);
  }

}
