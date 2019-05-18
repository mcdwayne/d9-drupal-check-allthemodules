<?php

namespace Drupal\add_to_head\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

class AddToHeadParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    $settings = add_to_head_get_settings();
    return array_key_exists($value, $settings) ? $settings[$value] : FALSE;
  }

  public function applies($definition, $name, Route $route) {
    // Stop this running on anything other than the add_to_head_profile type.
    // This breaks router items such as node/edit etc if we dont catch it.
    return isset($definition['type']) && $definition['type'] == 'add_to_head_profile' ? TRUE : FALSE;
  }
}
