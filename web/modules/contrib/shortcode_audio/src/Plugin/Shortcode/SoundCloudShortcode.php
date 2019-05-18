<?php

namespace Drupal\shortcode_audio\Plugin\Shortcode;

use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Html;

/**
 * Provides a shortcode for SoundCloud posts.
 *
 * @Shortcode(
 *   id = "soundcloud",
 *   title = @Translation("SoundCloud Shortcode"),
 *   description = @Translation("Embeds a SoundCloud post using shortcodes")
 * )
 */
class SoundCloudShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process($attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    $attributes = $this->getAttributes(array('class' => 'soundcloud'), $attributes);

    $text = Html::decodeEntities($text);
    $url = UrlHelper::parse($text);
    $url = strip_tags($url['path']);
    $class = $attributes['class'];

    return '<div class="shortcode-soundcloud ' . $class . '" data="' . $url . '"></div>';
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = array();
    $output[] = '<strong>' . t('[soundcloud]https://soundcloud.com/forss/flickermood[/soundcloud]') . '</strong> ';
    if ($long) {
      $output[] = t('Outputs HTML which will get the URL to use to display an embedded soundcloud post');
    }
    else {
      $output[] = t('Outputs HTML to display an embedded soundcloud post');
    }

    return implode(' ', $output);
  }

}
