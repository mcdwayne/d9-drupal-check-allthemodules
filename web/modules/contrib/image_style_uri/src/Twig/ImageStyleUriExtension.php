<?php

namespace Drupal\twig_image_style_url\Twig;

use Drupal\image\Entity\ImageStyle;

/**
 * Class DefaultService.
 *
 * @package Drupal\twig_image_style_url\Twig
 */
class ImageStyleUriExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'image_style_url';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction('image_style_uri',
        array($this, 'imageStyleUri'),
        array('is_safe' => array('html'))
      ),
    );
  }

  /**
   * Argument 1: uri. Argument 2: style.
   */
  public function imageStyleUri($uri, $style) {
    return ImageStyle::load($style)->buildUrl($uri);
  }

}
