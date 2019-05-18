<?php

namespace Drupal\cision_feeds\Feeds\Fetcher;

use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Result\RawFetcherResult;

/**
 * Defines an HTTP fetcher.
 *
 * @FeedsFetcher(
 *   id = "cision_fetch",
 *   title = @Translation("Download from cision"),
 *   description = @Translation("Downloads data from a URL"),
 *   form = {
 *     "feed" = "Drupal\cision_feeds\Feeds\Fetcher\Form\CisionFetcherFeedForm",
 *   }
 * )
 */
class CisionFetcher extends PluginBase implements  FetcherInterface {

  /**
   * Constructs an CisionFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    return new RawFetcherResult($feed->getSource());
  }
}
