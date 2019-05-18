<?php

namespace Drupal\google_feeds\Plugin\views\style;

use Drupal\views\Plugin\views\style\Rss;

/**
 * Style plugin to render a Google Shopping rss feed.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "google_shopping_feed",
 *   title = @Translation("Google shopping"),
 *   help = @Translation("Google Shopping Feed"),
 *   theme = "views_view_rss_google_shopping_feed",
 *   display_types = { "feed" }
 * )
 */
class GoogleShoppingRss extends Rss {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    return $options;
  }

}
