<?php

namespace Drupal\video_embed_facebook\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "facebook",
 *   title = @Translation("Facebook")
 * )
 */
class Facebook extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    // @todo, consider using the JavaScript version, however iframes are less
    // impact to page load and also don't grant JS access to your website to
    // Facebook.
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('https://www.facebook.com/video/embed?video_id=%s', $this->getVideoId(), $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return sprintf('https://graph.facebook.com/%d/picture', $this->getVideoId());
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www\.)?facebook.com\/([\w-\.]*\/videos\/|video\.php\?v\=)(?<id>[0-9]*)\/?$/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
