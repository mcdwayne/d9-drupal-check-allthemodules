<?php

namespace Drupal\cision_feed\Feeds\Fetcher;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\StateInterface;
use Drupal\cision_feed\Result\CisionFetcherResult;

/**
 * Defines an Cision fetcher.
 *
 * @FeedsFetcher(
 *   id = "cision_fetch",
 *   title = @Translation("Cision fetcher"),
 *   description = @Translation("Downloads data from a URL"),
 *   form = {
 *     "configuration" = "Drupal\cision_feed\Feeds\Fetcher\Form\CisionFetcherForm",
 *     "feed" = "Drupal\cision_feed\Feeds\Fetcher\Form\CisionFetcherFeedForm",
 *   },
 * )
 */
class CisionFetcher extends PluginBase implements FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    return new CisionFetcherResult($feed, $this->getConfiguration());
  }

  /**
   * Returns all different message types available in cision.
   *
   * @return array
   *   An array of all message types.
   */
  public function getTypes() {
    return [
      'KMK' => t('Annual Financial statement'),
      'RDV' => t('Annual Report'),
      'PRM' => t('Company Announcement'),
      'RPT' => t('Interim Report'),
      'INB' => t('Invitation'),
      'NBR' => t('Newsletter'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $options = $this->getTypes();

    return [
      'types' => array_keys($options),
      'page_size' => 50,
      'page_index' => 1,
      'start_date' => '',
      'end_date' => '',
      'language' => 'en',
    ];
  }

}
