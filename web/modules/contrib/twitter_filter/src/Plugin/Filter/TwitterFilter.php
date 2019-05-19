<?php

namespace Drupal\twitter_filter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to convert Twitter #hashtags and @usernames into links.
 *
 * @Filter(
 *   id = "twitter_filter",
 *   title = @Translation("Twitter filter"),
 *   description = @Translation("Convert Twitter #hashtags and @usernames into links"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "link_hashtags" = "hashtag_page",
 *     "link_usernames" = "user_page"
 *   }
 * )
 */
class TwitterFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['link_hashtags'] = array(
      '#type' => 'select',
      '#title' => $this->t('Link #hashtags to'),
      '#options' => array(
        'hashtag_page' => $this->t('Twitter hashtag page'),
        'search_page' => $this->t('Twitter search page'),
      ),
      '#default_value' => $this->settings['link_hashtags'],
    );

    $form['link_usernames'] = array(
      '#type' => 'select',
      '#title' => $this->t('Link @usernames to'),
      '#options' => array(
        'user_page' => $this->t('Twitter user page'),
        'search_page' => $this->t('Twitter search page'),
      ),
      '#default_value' => $this->settings['link_usernames'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Twitter #hashtags and @usernames turn into links automatically.');
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = self::processHashtag($text, $this->settings['link_hashtags']);
    $text = self::processUsername($text, $this->settings['link_usernames']);

    return new FilterProcessResult($text);
  }

  /**
   * Convert Twitter #hashtags into links.
   */
  public static function processHashtag($text, $link_hashtags) {
    if ($link_hashtags == 'search_page') {
      return preg_replace('/(^|\s)#(\w*[a-zA-Z_]+\w*)/', '\1<a class="twitter-hashtag" href="https://twitter.com/search?q=%23\2">#\2</a>', (string) $text);
    }
    else {
      return preg_replace('/(^|\s)#(\w*[a-zA-Z_]+\w*)/', '\1<a class="twitter-hashtag" href="https://twitter.com/hashtag/\2">#\2</a>', (string) $text);
    }

    Drupal::url();
  }

  /**
   * Converts Twitter @usernames into links.
   */
  public static function processUsername($text, $link_usernames) {
    if ($link_usernames == 'search_page') {
      return preg_replace('/(^|\s)@(\w*[a-zA-Z_]+\w*)/', '\1<a class="twitter-username" href="https://twitter.com/search?q=%40\2">@\2</a>', (string) $text);
    }
    else {
      return preg_replace('/(^|\s)@(\w*[a-zA-Z_]+\w*)/', '\1<a class="twitter-username" href="https://twitter.com/\2">@\2</a>', (string) $text);
    }
  }

}
