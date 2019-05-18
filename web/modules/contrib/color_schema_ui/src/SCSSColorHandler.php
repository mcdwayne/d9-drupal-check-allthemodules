<?php

namespace Drupal\color_schema_ui;

class SCSSColorHandler {

  private $colorNames = [
    'site-background-color',
    'peripheral-color-primary',
    'peripheral-color-secondary',
    'header-background-color',
    'content-color',
    'font-content-color',
    'font-header-color',
    'font-footer-color'
  ];

  public function replaceColors(string $scss, array $colorsToReplace): string {
    foreach ($colorsToReplace as $colorName => $colorValue) {
      $colorName = str_replace('_', '-', $colorName);
      $scss = \preg_replace('/\$' . $colorName . '\:.*\;/', '$' . $colorName . ': ' . $colorValue . ';', $scss);
    }

    return $scss;
  }

  public function getInitialColors(string $scss): array {
    $initialColors = [];

    foreach ($this->colorNames as $colorName) {
      $matches = null;
      preg_match('/\$' . $colorName . '\:(.*?)\;/', $scss, $matches);

      if (\count($matches, 2)) {
        $initialColors[$colorName] = trim($matches['1']);
      }
    }

    return $initialColors;
  }

}
