<?php

namespace Drupal\react_block\Plugin\Derivative;

use Drupal\pdb\Plugin\Derivative\PdbBlockDeriver;

/**
 * Derives block plugin definitions for React components.
 */
class ReactBlockDeriver extends PdbBlockDeriver {

  const TYPE = 'react-block';

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $definitions = parent::getDerivativeDefinitions($base_plugin_definition);

    return array_filter($definitions, function (array $definition) {
      return $definition['info']['presentation'] == self::TYPE;
    });
  }

}
