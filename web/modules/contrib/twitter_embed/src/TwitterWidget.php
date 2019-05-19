<?php

namespace Drupal\twitter_embed;

use Drupal\Core\Url;

/**
 * Class TwitterWidget.
 */
abstract class TwitterWidget implements TwitterWidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function getAvailableLanguages() {
    return [
      '' => t('Automatic'),
      'en' => t('English (default)'),
      'ar' => t('Arabic'),
      'bn' => t('Bengali'),
      'cs' => t('Czech'),
      'da' => t('Danish'),
      'de' => t('German'),
      'el' => t('Greek'),
      'es' => t('Spanish'),
      'fa' => t('Persian'),
      'fi' => t('Finnish'),
      'fil' => t('Filipino'),
      'fr' => t('French'),
      'he' => t('Hebrew'),
      'hi' => t('Hindi'),
      'hu' => t('Hungarian'),
      'id' => t('Indonesian'),
      'it' => t('Italian'),
      'ja' => t('Japanese'),
      'ko' => t('Korean'),
      'msa' => t('Malay'),
      'nl' => t('Dutch'),
      'no' => t('Norwegian'),
      'pl' => t('Polish'),
      'pt' => t('Portuguese'),
      'ro' => t('Romanian'),
      'ru' => t('Russian'),
      'sv' => t('Swedish'),
      'th' => t('Thai'),
      'tr' => t('Turkish'),
      'uk' => t('Ukrainian'),
      'ur' => t('Urdu'),
      'vi' => t('Vietnamese'),
      'zh-cn' => t('Chinese (Simplified)'),
      'zh-tw' => t('Chinese (Traditional)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget(array $configuration) {
    $build['twitter_widget'] = [
      '#type' => 'link',
      '#title' => $this->createLabel($configuration),
      '#url' => $this->createUrl($configuration),
      '#attributes' => $this->createAttributes($configuration),
      '#attached' => [
        'library' => ['twitter_embed/twitter_widgets'],
      ],
    ];
    return $build;
  }

  /**
   * Returns a Twitter Url depending on the configuration.
   *
   * @param array $configuration
   *   List of selected configuration.
   *
   * @return \Drupal\Core\Url
   *   The Twitter Url.
   */
  private function createUrl(array $configuration) {
    $uri = 'https://twitter.com/' . $configuration['username'];
    // @todo refactor 'display_style' used for Button instead of 'type'
    // @todo complete configuration
    switch ($configuration['type']) {
      case 'list':
        $uri .= '/lists/' . $configuration['type_value'];
        break;

      case 'collection':
        $uri .= '/timelines/' . $configuration['type_value'];
        break;

      case 'likes':
        $uri .= '/likes';
        break;
    }
    return Url::fromUri($uri);
  }

  /**
   * Returns a label depending on the configuration.
   *
   * @param array $configuration
   *   List of selected configuration.
   *
   * @return string
   *   Label.
   */
  private function createLabel(array $configuration) {
    // @todo handle several cases from the configuration
    return t('Tweets by @@username', ['@username' => $configuration['username']]);
  }

  /**
   * Returns attributes depending on the configuration.
   *
   * @param array $configuration
   *   List of selected configuration.
   *
   * @return array
   *   List of attributes.
   */
  private function createAttributes(array $configuration) {
    $result = [];
    // Common data-attributes.
    // @todo review common data-attributes and polymorphism for differences.
    $result['class'] = ['twitter-' . $configuration['display_style']];
    if (!empty($configuration['language'])) {
      $result['lang'] = $configuration['language'];
    }

    // @todo complete data-attribute list and check conditions depending on the type
    // Timeline specific data-attributes
    if (!empty($configuration['theme'])) {
      $result['data-theme'] = $configuration['theme'];
    }
    if (!empty($configuration['chrome'])) {
      $options = array_keys(array_filter($configuration['chrome']));
      if (count($options)) {
        $result['data-chrome'] = implode(' ', $options);
      }
    }
    if (!empty($configuration['width'])) {
      $result['data-width'] = $configuration['width'];
    }
    if (!empty($configuration['height'])) {
      $result['data-height'] = $configuration['height'];
    }
    if (!empty($configuration['link_color'])) {
      $result['data-link-color'] = $configuration['link_color'];
    }
    if (!empty($configuration['border_color'])) {
      $result['data-border-color'] = $configuration['border_color'];
    }
    if (!empty($configuration['tweet_limit'])) {
      $result['data-tweet-limit'] = $configuration['tweet_limit'];
    }
    if (!empty($configuration['aria_polite'])) {
      $result['aria-polite'] = $configuration['aria_polite'];
    }

    // Button specific data-attributes.
    if ($configuration['hide_username']) {
      $result['data-show-screen-name'] = 'false';
    }
    if ($configuration['hide_followers_count']) {
      $result['data-show-count'] = 'false';
    }
    if (!empty($configuration['size'])) {
      $result['data-size'] = 'large';
    }

    return $result;
  }

}
