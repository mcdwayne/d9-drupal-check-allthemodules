<?php

namespace Drupal\styleswitcher\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Converts parameters for upcasting style names to full objects.
 */
class StyleswitcherStyleConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $defaults += ['theme' => '', 'type' => 'custom'];
    return styleswitcher_style_load($value, $defaults['theme'], $defaults['type']);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'styleswitcher_style';
  }

}
