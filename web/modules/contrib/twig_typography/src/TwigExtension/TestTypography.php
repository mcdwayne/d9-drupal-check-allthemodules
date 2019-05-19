<?php

namespace Drupal\twig_typography\TwigExtension;

/**
 * Provides a typography twig filter.
 *
 * Used to provide mock returns for functions not available to PHPUnit.
 *
 * @package Drupal\twig_typography\TwigExtension
 */
class TestTypography extends Typography {

  /**
   * {@inheritdoc}
   */
  public static function getThemeName() {
    return 'test_theme';
  }

  /**
   * {@inheritdoc}
   */
  public static function getFilePath($theme_name) {
    return 'themes/custom/test_theme/typography_defaults.yml';
  }

}
