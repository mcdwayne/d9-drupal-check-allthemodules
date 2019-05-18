<?php

namespace Drupal\block_in_form\Routing;

use Drupal\block_in_form\BlockInFormCommon;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting block inf form config ids to block in form object.
 */
class BlockInFormConverter implements ParamConverterInterface {

  use BlockInFormCommon;

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return isset($definition['type']) && $definition['type'] == 'block_in_form';
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $identifiers = explode('.', $value);
    if (count($identifiers) != 6) {
      return;
    }

    return $this->loadBlock($identifiers[4], $identifiers[0], $identifiers[1], $identifiers[2], $identifiers[3]);
  }
}
