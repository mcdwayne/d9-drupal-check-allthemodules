<?php

namespace Drupal\google_feeds\Plugin\views\style;

use Drupal\views\Plugin\views\style\Rss;

/**
 * Style plugin to render a Google News rss feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "google_news_feed",
 *   title = @Translation("Google News"),
 *   help = @Translation("Google News Feed"),
 *   theme = "views_view_rss_google_news_feed",
 *   display_types = { "feed" }
 * )
 */
class GoogleNewsRss extends Rss {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

}
