<?php

/**
 * @file
 * Theme for Google Feeds views rss.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;

/**
 * Implements template_preprocess_views_view_row_rss().
 */
function template_preprocess_views_view_row_rss_google_news_feed(&$variables) {
  $item = $variables['row'];
  $variables['link'] = $item->elements[0]['value'];

  // The description is the only place where we should find HTML.
  // @see https://validator.w3.org/feed/docs/rss2.html#hrelementsOfLtitemgt
  // If we have a render array, render it here and pass the result to the
  // template, letting Twig autoescape it.
  if (isset($item->description) && is_array($item->description)) {
    $variables['description'] = (string) \Drupal::service('renderer')->render($item->description);
  }

  $variables['item_elements'] = [];
  foreach ($item->elements as $element) {
    if (isset($element['attributes']) && is_array($element['attributes'])) {
      $element['attributes'] = new Attribute($element['attributes']);
    }
    $variables['item_elements'][] = $element;
  }
}

/**
 * Implements template_preprocess_views_view_row_rss().
 */
function template_preprocess_views_view_row_rss_google_shopping_feed(&$variables) {
  $item = $variables['row'];
  $variables['link'] = $item->elements[0]['value'];

  // The description is the only place where we should find HTML.
  // @see https://validator.w3.org/feed/docs/rss2.html#hrelementsOfLtitemgt
  // If we have a render array, render it here and pass the result to the
  // template, letting Twig autoescape it.
  if (isset($item->description) && is_array($item->description)) {
    $variables['description'] = (string) \Drupal::service('renderer')->render($item->description);
  }

  $variables['item_elements'] = [];
  foreach ($item->elements as $element) {
    if (isset($element['attributes']) && is_array($element['attributes'])) {
      $element['attributes'] = new Attribute($element['attributes']);
    }
    $variables['item_elements'][] = $element;
  }
}


/**
 * Implements template_preprocess_views_view_rss().
 */
function template_preprocess_views_view_rss_google_news_feed(&$variables) {
  $view = $variables['view'];
  $items = $variables['rows'];
  $style = $view->style_plugin;

  $config = \Drupal::config('system.site');

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $variables['description'] = Html::decodeEntities(strip_tags($style->getDescription()));

  if ($view->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->getTitle();
  }
  $variables['title'] = $title;

  // Figure out which display has a path we're using for this feed. If
  // there isn't one, use the global $base_url.
  $link_display_id = $view->display_handler->getLinkDisplay();
  if ($link_display_id && $view->displayHandlers->get($link_display_id)) {
    $url = $view->getUrl(NULL, $link_display_id);
  }

  /** @var \Drupal\Core\Url $url */
  if ($url) {
    $url_options = ['absolute' => TRUE];
    if (!empty($view->exposed_raw_input)) {
      $url_options['query'] = $view->exposed_raw_input;
    }

    // Compare the link to the default home page; if it's the default home page,
    // just use $base_url.
    $url_string = $url->setOptions($url_options)->toString();
    if ($url_string === Url::fromUserInput($config->get('page.front'))->toString()) {
      $url_string = Url::fromRoute('<front>')->setAbsolute()->toString();
    }

    $variables['link'] = $url_string;
  }

  $variables['namespaces'] = new Attribute($style->namespaces);
  $variables['items'] = $items;
  $variables['channel_elements'] = \Drupal::service('renderer')->render($style->channel_elements);

  // During live preview we don't want to output the header since the contents
  // of the feed are being displayed inside a normal HTML page.
  if (empty($variables['view']->live_preview)) {
    $variables['view']->getResponse()->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');
  }
}

/**
 * Implements template_preprocess_views_view_rss().
 */
function template_preprocess_views_view_rss_google_shopping_feed(&$variables) {
  $view = $variables['view'];
  $items = $variables['rows'];
  $style = $view->style_plugin;

  $config = \Drupal::config('system.site');

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $variables['description'] = Html::decodeEntities(strip_tags($style->getDescription()));

  if ($view->display_handler->getOption('sitename_title')) {
    $title = $config->get('name');
    if ($slogan = $config->get('slogan')) {
      $title .= ' - ' . $slogan;
    }
  }
  else {
    $title = $view->getTitle();
  }
  $variables['title'] = $title;

  // Figure out which display has a path we're using for this feed. If
  // there isn't one, use the global $base_url.
  $link_display_id = $view->display_handler->getLinkDisplay();
  if ($link_display_id && $view->displayHandlers->get($link_display_id)) {
    $url = $view->getUrl(NULL, $link_display_id);
  }

  /** @var \Drupal\Core\Url $url */
  if ($url) {
    $url_options = ['absolute' => TRUE];
    if (!empty($view->exposed_raw_input)) {
      $url_options['query'] = $view->exposed_raw_input;
    }

    // Compare the link to the default home page; if it's the default home page,
    // just use $base_url.
    $url_string = $url->setOptions($url_options)->toString();
    if ($url_string === Url::fromUserInput($config->get('page.front'))->toString()) {
      $url_string = Url::fromRoute('<front>')->setAbsolute()->toString();
    }

    $variables['link'] = $url_string;
  }

  $variables['namespaces'] = new Attribute($style->namespaces);
  $variables['items'] = $items;
  $variables['channel_elements'] = \Drupal::service('renderer')->render($style->channel_elements);

  // During live preview we don't want to output the header since the contents
  // of the feed are being displayed inside a normal HTML page.
  if (empty($variables['view']->live_preview)) {
    $variables['view']->getResponse()->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');
  }
}
