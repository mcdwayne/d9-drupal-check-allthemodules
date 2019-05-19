<?php

namespace Drupal\stacks\TwigExtension;

use Drupal\stacks\Widget\WidgetTemplates;

/**
 * Class GetThemePath.
 * @package Drupal\stacks\TwigExtension
 */
class GetThemePath extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('getStacksPath', [$this, 'getStacksPath']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'stacks_getthemepath.twig_extension';
  }

  /**
   * Takes a path and returns the absolute path to include the file. This file
   * needs to be in the stacks directory of your frontend theme.
   *
   * @param $path string: The path of the file in the theme we want to return.
   * @return string: Absolute path to this file.
   */
  public function getStacksPath($path) {
    // Get the path to the frontend theme.
    $frontend_theme = WidgetTemplates::templateDir();

    // Return correct path to include this file.
    return '/' . $frontend_theme . '/' . $path;
  }

}
