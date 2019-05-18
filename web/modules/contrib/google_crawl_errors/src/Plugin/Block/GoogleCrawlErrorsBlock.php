<?php

namespace Drupal\google_crawl_errors\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\google_crawl_errors\GoogleCrawlErrors;

/**
 * Provides block of Google Console crawl errors result.
 *
 * @Block(
 * id = "google_crawl_errors_block",
 * admin_label = @Translation("Google crawl errors block"),
 * category = @Translation("Blocks")
 * )
 */
class GoogleCrawlErrorsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('google_crawl_errors.settings');
    $category = 'notFound';
    $platform = 'web';

    $gce = new GoogleCrawlErrors();
    $data = $gce->getResultData($config->get('site_id'), $category, $platform);

    $contents = $gce->prepareOutput($data, 10);

    return [
      '#title' => t('Google crawl errors'),
      '#theme' => 'google_crawl_errors_block',
      '#contents' => $contents,
      '#show_full_link' => TRUE,
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => ['google_crawl_errors/report-block'],
      ],
    ];

  }

}
