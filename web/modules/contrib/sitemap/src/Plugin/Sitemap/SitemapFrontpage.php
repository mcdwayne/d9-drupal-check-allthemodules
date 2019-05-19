<?php

namespace Drupal\sitemap\Plugin\Sitemap;

use Drupal\sitemap\SitemapBase;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a link to the front page for the sitemap.
 *
 * @Sitemap(
 *   id = "sitemap_frontpage",
 *   title = @Translation("Site front page"),
 *   description = @Translation("Displays a sitemap link for the site front page."),
 *   settings = {
 *     "title" = "Front page",
 *     "rss" = "rss.xml",
 *   },
 *   enabled = TRUE,
 * )
 */
class SitemapFrontpage extends SitemapBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['rss'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Feed URL'),
      '#default_value' => $this->settings['rss'],
      '#description' => $this->t('Specify the RSS feed for the front page. If you do not wish to display a feed, leave this field blank.'),
    ];
    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function view() {

    $title = $this->settings['title'];
    $content = Link::fromTextAndUrl(t('Front page of %sn', ['%sn' => \Drupal::config('system.site')->get('name')]), Url::fromRoute('<front>', [], ['html' => TRUE]))->toString();
    $attributes = new Attribute();

    if ($this->settings['rss']) {
      $feed_icon = [
        '#theme' => 'sitemap_feed_icon',
        '#url' => $this->settings['rss'],
        '#name' => 'front page',
      ];
      $rss_link = \Drupal::service('renderer')->render($feed_icon);

      // Ask Sitemap config for its RSS display settings.
      if ($this->sitemapConfig->get('rss_display') == 'right') {
        $content .= ' ' . $rss_link;
      }
      else {
        $attributes->addClass('sitemap-rss-left');
        $content = $rss_link . ' ' . $content;
      }
    }

    $attributes->addClass('sitemap-frontpage');

    return [
      '#theme' => 'sitemap_item',
      '#title' => $title,
      '#content' => ['#markup' => $content],
      '#attributes' => $attributes,
    ];
  }

}
