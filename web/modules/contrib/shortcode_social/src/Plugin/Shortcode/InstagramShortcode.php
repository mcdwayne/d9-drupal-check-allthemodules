<?php

namespace Drupal\shortcode_social\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;

/**
 * Provides a shortcode for Instagram posts.
 *
 * @Shortcode(
 *   id = "instagram",
 *   title = @Translation("Instagram Shortcode"),
 *   description = @Translation("Embeds a Instagram post using shortcodes")
 * )
 */
class InstagramShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes(array(
      'class' => 'instagram',
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
    $output[] = '<strong>' . t('[instagram]https://instagram.com/p/8Jgw8AhQTO[/instagram]') . '</strong> ';
    if ($long) {
      $output[] = t('Outputs HTML which will get the URL to use to display an embedded instagram image');
    }
    else {
      $output[] = t('Outputs HTML to display an embedded instagram image');
    }

    return implode(' ', $output);
  }

}
