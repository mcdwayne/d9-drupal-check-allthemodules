<?php

namespace Drupal\menu_twig\Twig;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Twig extension with some useful functions.
 *
 * The menu_twig_link extention replace the link extension available in
 *  menu link.
 */
class MenuTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      'link' => new \Twig_Function_Function(array('Drupal\menu_twig\Twig\MenuTwigExtension', 'getLink')),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'menu_twig';
  }

  /**
   * Gets a rendered link from a url object.
   *
   * @param string $text
   *   The link text for the anchor tag as a translated string.
   * @param \Drupal\Core\Url|string $url
   *   The URL object or string used for the link.
   * @param array|\Drupal\Core\Template\Attribute $attributes
   *   An optional array or Attribute object of link attributes.
   *
   * @return array
   *   A render array representing a link to the given URL.
   */
  public static function getLink($text, $url, $attributes = []) {
    if (!$url instanceof Url) {
      $url = Url::fromUri($url);
    }
    if ($attributes) {
      if ($attributes instanceof Attribute) {
        $attributes = $attributes->toArray();
      }
      if ($existing_attributes = $url->getOption('attributes')) {
        $attributes = array_merge($existing_attributes, $attributes);
      }
      $url->setOption('attributes', $attributes);
    }
    $menu_twig = (NULL !== $url->getOption('menu_twig')) ? $url->getOption('menu_twig') : NULL;
    $html_markup = NULL;
    if (isset($menu_twig['menu_twig_text'])) {
      $url->setOption('menu_twig', NULL);
      if ($menu_twig !== "") {
        $html_markup = \Drupal::service('twig')->renderInline($menu_twig['menu_twig_text']['value'], [
          'title' => $text,
          'url' => $url,
          'attributes' => $attributes,
        ]);
      }
      $format = isset($menu_twig['menu_twig_text']['format']) ? $menu_twig['menu_twig_text']['format'] : filter_fallback_format();
      $html_markup = trim(check_markup($html_markup, $format));
      if (isset($menu_twig['is_override']) && ($menu_twig['is_override'] == 1)) {
        $build = [
          '#type' => 'markup',
          '#markup' => $html_markup,
        ];
        return $build;
      }
    }
    // The text has been processed by twig already, convert it to a safe object
    // for the render system.
    if ($text instanceof \Twig_Markup) {
      $text = Markup::create($text);
    }

    $build = [
      '#type' => 'link',
      '#title' => $text,
      '#url' => $url,
      '#suffix' => $html_markup,
    ];
    return $build;
  }

}
