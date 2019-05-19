<?php

namespace Drupal\twig_svg\TwigExtension;

/**
 * Adds a twig template extension to easily add an SVG.
 */
class TwigSvg extends \Twig_Extension {

  /**
   * List the custom Twig functions.
   *
   * @return array
   *   The twig function.
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('icon', [$this, 'getInlineSvg']),
    ];
  }

  /**
   * Get the name of the service listed in twig_svg.services.yml.
   *
   * @return string
   *   The service name.
   */
  public function getName() {
    return "twig_svg.twig.extension";
  }

  /**
   * Callback for the icon() Twig function.
   *
   * @param string $name
   *   The name of the icon to use.
   * @param string $title
   *   The title to apply to the icon.
   * @param array $classes
   *    Additional classes to apply to the icon.
   *
   * @return array
   *   The SVG array.
   */
  public static function getInlineSvg($name, $title = '', $classes = []) {
    $default_classes = [
      'icon',
      'icon--' . $name,
    ];
    $classes = array_merge($default_classes, $classes);

    $svg = '<svg class="' . implode(' ', $classes) . '" role="img" ';
    if (!empty($title)) {
      $svg .= 'title="{{ title }}" ';
    }
    $svg .= 'xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#{{ name }}"></use></svg>';

    return [
      '#type' => 'inline_template',
      '#template' => '<span class="icon__wrapper">' . $svg . '</span>',
      '#context' => [
        'title' => $title,
        'name' => $name,
      ],
    ];
  }

}
