<?php

namespace Drupal\shortcode_social\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;

/**
 * Provides a shortcode for Twitter posts.
 *
 * @Shortcode(
 *   id = "twitter",
 *   title = @Translation("Twitter Shortcode"),
 *   description = @Translation("Embeds a twitter post using shortcodes")
 * )
 */
class TwitterShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes(array(
      'class' => 'twitter',
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
    $output[] = '<strong>' . t('[twitter]https://twitter.com/Interior/status/463440424141459456[/twitter]') . '</strong> ';
    if ($long) {
      $output[] = t('Outputs HTML which will get the URL to use to display an embedded twitter post');
    }
    else {
      $output[] = t('Outputs HTML to display an embedded twitter post');
    }

    return implode(' ', $output);
  }

}
