<?php

namespace Drupal\shortcode_social\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;

/**
 * Provides a shortcode for Flickr posts.
 *
 * @Shortcode(
 *   id = "flickr",
 *   title = @Translation("Flickr Shortcode"),
 *   description = @Translation("Embeds a Flickr post using shortcodes")
 * )
 */
class FlickrShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes(array(
      'class' => 'flickr',
    ),
      $attributes
    );

    $text = Html::decodeEntities($text);
    $url = UrlHelper::parse($text);
    $url = strip_tags($url['path']);
    $class = $attributes['class'];

    return '<div class="shortcode-social ' . $class . '" data="' . $url . '"></div>';
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<strong>' . t('[flickr]https://www.flickr.com/photos/judgebusinessschool/sets/72157644210270121/[/flickr]') . '</strong> ';
    if ($long) {
      $output[] = t('Outputs HTML which will get the URL to use to display an embedded flickr image/gallery');
    }
    else {
      $output[] = t('Outputs HTML to display an embedded flickr image/gallery');
    }

    return implode(' ', $output);
  }

}
