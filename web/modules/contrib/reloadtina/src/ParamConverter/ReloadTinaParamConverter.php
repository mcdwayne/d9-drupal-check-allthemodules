<?php

namespace Drupal\reloadtina\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\Routing\Route;

/**
 * Load image style and apply multiplier.
 */
class ReloadTinaParamConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    @list($image_style_name, $multiplier) = explode('-', $value, 2);
    $image_style = ImageStyle::load($image_style_name);
    $image_style->multiplier = $multiplier;
    return $image_style;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'reloadtina.image_style');
  }
}
