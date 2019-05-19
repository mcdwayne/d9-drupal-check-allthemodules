<?php

namespace Drupal\stacks\TwigExtension;

use Drupal;

/**
 * Class ImageHtml.
 * @package Drupal\stacks\TwigExtension
 */
class ImageHtml extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('image', [$this, 'imageHtml']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'stacks_imagehtml.twig_extension';
  }

  /**
   * Takes a uri object and image style string, and return the image html. If
   * an image style string is not specified, use the original url. Also takes
   * a classes string that is attached to the <img> tag.
   */
  public static function imageHtml($image, $image_style = '', $classes = '') {
    $variables['attributes'] = [
      'class' => [],
      'alt' => $image['alt'],
    ];

    // Determine the dimensions of the image.
    list($width, $height) = @getimagesize($image['url']);

    $dimensions = ['width' => $width, 'height' => $height];

    // Define variables, based on if this is using an image style.
    if (!empty($image_style)) {
      $style = Drupal::entityTypeManager()
        ->getStorage('image_style')
        ->load($image_style);
      $url = $style->buildUrl($image['uri']);

      // Change dimensions based on image style.
      $style->transformDimensions($dimensions, $image['uri']);

      // Add image style as class.
      $variables['attributes']['class'][] = 'image-style-' . $image_style;
    }
    else {
      $url = $image['url'];
    }

    // Add additional classes to image.
    if (!empty($variables['attributes']['class'])) {
      $variables['attributes']['class'][] = $classes;
    }

    $image = [
      '#theme' => 'image',
      '#width' => $dimensions['width'],
      '#height' => $dimensions['height'],
      '#attributes' => $variables['attributes'],
      '#uri' => $url,
    ];

    return $image;
  }

}
