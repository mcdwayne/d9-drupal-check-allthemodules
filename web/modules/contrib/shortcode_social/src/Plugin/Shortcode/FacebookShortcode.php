<?php

namespace Drupal\shortcode_social\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;

/**
 * Provides a shortcode for Facebook posts.
 *
 * @Shortcode(
 *   id = "facebook",
 *   title = @Translation("Facebook Shortcode"),
 *   description = @Translation("Embeds a Facebook post using shortcodes")
 * )
 */
class FacebookShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes(array(
      'class' => 'facebook',
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
    $output[] = '<strong>' . t('[facebook]https://www.facebook.com/FacebookDevelopers/posts/10152128760693553[/facebook]') . '</strong> ';
    if ($long) {
      $output[] = t('Outputs HTML which will get the URL to use to display an embedded facebook post');
    }
    else {
      $output[] = t('Outputs HTML to display an embedded facebook post');
    }

    return implode(' ', $output);
  }

}
