<?php

namespace Drupal\amp\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides AMP social post elements.
 *
 * Provides amp-facebook, amp-twitter, amp-instagram, amp-pinterest. The
 * provider is deduced from the url.
 *
 * @parameter string #url: The social url.
 * @parameter string placeholder: Placeholder for amp-twitter.
 * @parameter array attributes:
 *   The HTML attributes for amp-social-post:
 *   - layout: The layout of the element.
 *   - height: The height of the element.
 *   - width: The width of the element.
 *   - data-embed-as: Embed as post or video for amp-facebook.
 *   - data-align-center: Center align or not for amp-facebook.
 *
 * Example usage:
 *  $renderElement = [
 *    '#type' => 'amp_social_post',
 *    '#url' => 'https://twitter.com/cramforce/status/638793490521001985',
 *  ];
 *
 * @RenderElement("amp_social_post")
 */
class AmpSocialPost extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#theme' => 'amp_social_post_theme',
      '#pre_render' => array(
        array($class, 'preRenderSocialPost'),
      ),
      '#url' => NULL,
      '#placeholder' => NULL,
      '#attributes' => [
        'layout' => 'responsive',
        'width' => NULL,
        'height' => NULL,
        'data-embed-as' => 'post',
        'data-align-center' => NULL,
      ],
      '#attached' => [
        'library' => [
          'amp/runtime',
        ],
      ],
    );
  }

  /**
   * Pre-render callback.
   *
   * Processes the post and attaches libraries.
   */
  public static function preRenderSocialPost($element) {
    $url = $element['#url'];
    // If provider is invalid, the element is empty.
    if (!$provider = static::getProvider($url)) {
      return [];
    }
    else {
      $element['#provider'] = $provider;
    }

    // Set the url or id.
    switch ($provider) {
      case 'twitter':
        $element['#attributes']['data-tweetid'] = static::getId($url, $provider);
        break;

      case 'instagram':
        $element['#attributes']['data-shortcode'] = static::getId($url, $provider);
        break;

      case 'pinterest':
        $element['#attributes']['data-href'] = $url;
        $element['#attributes']['data-do'] = 'embedPin';
        break;

      default:
        $element['#attributes']['data-href'] = $url;
        break;

    }

    // Get rid of empty attributes.
    $element['#attributes'] = array_filter($element['#attributes']);

    // Attach the right library.
    $libraries = static::getLibraries();
    $element['#attached']['library'][] = $libraries[$provider];
    return $element;
  }

  /**
   * Option list of all providers.
   *
   * return array
   *   Array of names and labels for all supported providers.
   */
  public static function getProviders() {
    return [
      'facebook' => 'Facebook',
      'twitter' => 'Twitter',
      'instagram' => 'Instagram',
      'pinterest' => 'Pinterest',
    ];
  }

  /**
   * Provider libraries.
   *
   * return array
   *   Array of libraries required by all supported providers.
   */
  public static function getLibraries() {
    return [
      'facebook' =>  'amp/amp.facebook',
      'twitter' => 'amp/amp.twitter',
      'instagram' => 'amp/amp.instagram',
      'pinterest' => 'amp/amp.pinterest',
    ];
  }

  /**
   * Provider regex patterns.
   *
   * return array
   *   Array of regex patterns for all supported providers.
   * @see https://code.tutsplus.com/tutorials/advanced-regular-expression-tips-and-techniques--net-11011
   */
  public static function getPatterns() {
    return [
      'facebook' => [
        '@https?://(?:www\.)?(?<provider>facebook)\.com/?(.*/)?(?<id>[a-zA-Z0-9.]*)($|\?.*)@'
      ],
      'twitter' => [
        '@https?://(?:www\.)?(?<provider>twitter)\.com/(?<user>[a-z0-9_-]+)/(status(es){0,1})/(?<id>[\d]+)@i'
      ],
      'instagram' => [
        '@https?://(?:www\.)?(?<provider>instagram)\.com/p/(?<id>[a-z0-9_-]+)@i',
        '@https?://(?:www\.)?(?<provider>instagr\.am)/p/(?<id>[a-z0-9_-]+)@i',
      ],
      'pinterest' => [
        '@https?://(?:www\.)?(?<provider>pinterest)\.([a-zA-Z]+\.)?([a-zA-Z]+)/pin/(?<id>\d+)/?\s*$$@i',
      ],
    ];
  }

  /**
   * Find the provider from an url.
   *
   * @param $url
   *   The url.
   *
   * @return string
   *   The provider this url is valid for.
   */
  public static function getProvider($url) {
    $patterns = static::getPatterns();
    $providers = array_keys(static::getProviders());
    foreach ($providers as $provider) {
      foreach ($patterns[$provider] as $pattern) {
        if (preg_match($pattern, $url, $matches) && $matches['provider'] == $provider) {
          return $provider;
        }
      }
    }
    return FALSE;
  }

  /**
   * Get the provider id from an url.
   *
   * @param $url
   *   The url.
   *
   * @return string
   *   The id.
   */
  public static function getId($url, $provider) {
    $patterns = static::getPatterns();
    foreach ($patterns[$provider] as $pattern) {
      if (preg_match($pattern, $url, $matches)) {
        return $matches['id'];
      }
    }
    return FALSE;
  }

}
