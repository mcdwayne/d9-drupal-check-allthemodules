<?php

namespace Drupal\commerce_equiv_weight\TwigExtension;

use Drupal\physical\Weight;

/**
 * Provides Equivalency Weight-specific Twig extensions.
 */
class EquivalencyWeightTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('equiv_weight_format', [$this, 'formatEquivalencyWeight']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'commerce_equiv_weight.twig_extension';
  }

  /**
   * Formats a weight object.
   *
   * Examples:
   * {{ custom_weight.weight|equiv_weight_format }}
   *
   * @param \Drupal\Physical\Weight $weight
   *   The weight.
   *
   * @return string
   *   A formatted equivalency weight.
   *
   * @throws \InvalidArgumentException
   */
  public static function formatEquivalencyWeight(Weight $weight) {
    if (empty($weight)) {
      return '';
    }

    if (!($weight instanceof Weight)) {
      throw new \InvalidArgumentException('The "equiv_weight_format" filter must be given a weight object');
    }
    return commerce_equiv_weight_round($weight->getNumber()) . $weight->getUnit();
  }

}
