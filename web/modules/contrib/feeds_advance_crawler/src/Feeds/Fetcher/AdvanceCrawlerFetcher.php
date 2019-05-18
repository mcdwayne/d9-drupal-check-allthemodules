<?php

namespace Drupal\feeds_advance_crawler\Feeds\Fetcher;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\File\FileSystemInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\feeds\StateInterface;
use Drupal\feeds\Utility\Feed;
use Drupal\feeds\Result\FetcherResult;

/**
 * Defines an Advance Crawler Fetcher.
 *
 * @FeedsFetcher(
 *   id = "advance_crawler",
 *   title = @Translation("Advance Crawler"),
 *   description = @Translation("Downloads data from a URL through Nodejs Server"),
 *   form = {
 *     "configuration" = "Drupal\feeds_advance_crawler\Feeds\Fetcher\Form\AdvanceCrawlerFetcherForm",
 *     "feed" = "Drupal\feeds_advance_crawler\Feeds\Fetcher\Form\AdvanceCrawlerFetcherFeedForm",
 *   },
 *   arguments = {"@http_client", "@file_system"}
 * )
 */
class AdvanceCrawlerFetcher extends PluginBase implements FetcherInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Drupal file system helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs an UploadFetcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle client.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The Drupal file system helper.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ClientInterface $client, FileSystemInterface $file_system) {
    $this->client = $client;
    $this->fileSystem = $file_system;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $config = \Drupal::configFactory()->get('feeds_advance_crawler.settings');
    $sink = $this->fileSystem->tempnam('temporary://', 'advance_crawler_fetcher');
    $sink = $this->fileSystem->realpath($sink);
    $fetcher_type = $this->getConfiguration('fetcher_type');

    // Getting feed config
    $feed_config = $feed->getConfigurationFor($this);
    $url = "";
    $options = [];

    if (!isset($state->left)) {
      $state->left = 0;
      $state->total = 0;
      $state->left_html = "";
    }
    // Enabled inner page scraping
    if ($feed_config['inner_feeds_scraper']) {
      $options['context'] = $this->feedType->getParser()->getConfiguration('context');
      $options['left_html'] = $state->left_html;
      $options = array_merge($options, $feed_config);
    }

    // Enabled Pagination
    if ($feed_config['feeds_crawler'] && $state->left == 0) {
      // Batch fetching
      if (!isset($state->current_fetch)) {
        $state->total = $feed_config['no_of_pages'];
        $state->current_fetch = $feed_config['initial_value'];
      }
      // Delay
      if ($feed_config['delay']) {
        sleep($feed_config['delay']);
      }
      $url = $feed_config['url_pattern'];
      $url = str_replace('$index', $state->current_fetch, $url);
      $state->current_fetch = $state->current_fetch + $feed_config['increment'];
      $state->progress($state->total, $state->current_fetch);
    } else {
      $url = $feed->getSource();
    }

    $response = $this->get($url, $config, $fetcher_type, $options);
    $results = json_decode($response->getBody(), true);
    if ($results['status'] == true && $results['response']['statusCode'] == 200) {
      $body = $results['response']['body'];
      if ($feed_config['break_in_parts'] && $results['left']) {
        $state->left = $results['left'];
        $state->left_html = $results['leftHtml'];
        $state->progress(1,0);
      } else {
        $state->left = 0;
        $state->left_html = "";
        if (!$feed_config['feeds_crawler']) {
          $state->progress(0,0);
        } else if ($feed_config['feeds_crawler'] && $state->total < $state->current_fetch) {
          $state->progress(0,0);
        }
      }
      file_put_contents($sink, $body);
      return new FetcherResult($sink);
    } else {
      $args = ['%site' => $url, '%error' => $results['error']['message']];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }
  }

  /**
   * Performs a POST request.
   *
   * @param string $url
   *   The URL to GET.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The location where the downloaded content will be saved. This can be a
   *   resource, path or a StreamInterface object.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the POST request failed.
   *
   */
  protected function get(string $url, ImmutableConfig $config, string $fetcher_type, array $options = []) {
    $url = Feed::translateSchemes($url);

    $node_server_url = $config->get('nodejs_host') . ':' . $config->get('nodejs_port');
    $options['proxy'] = $config->get('proxy');

    if ($fetcher_type == 'static_fetcher') {
      $node_server_url .= '/get-static';
    } else {
      $node_server_url .= '/get-dynamic';
    }

    try {
      $response = $this->client->request('POST', $node_server_url, [
        'headers' => [ 'Content-Type' => 'application/json' ],
        'body' => json_encode([
          'url' => $url,
          'options' => $options,
        ]),
      ]);
    }
    catch (RequestException $e) {
      $args = ['%site' => $node_server_url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultFeedConfiguration() {
    return [
      'feeds_crawler' => TRUE,
      'no_of_pages' => '',
      'delay' => 0,
      'url_pattern' => '',
      'initial_value' => 1,
      'increment' => 1,
      'inner_feeds_scraper' => False,
      'context' => '',
      'link_selector' => '',
      'base_url' => '',
      'inner_page_selector' => '',
      'break_in_parts' => TRUE,
      'no_of_parts' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fetcher_type' => 'static_fetcher',
    ];
  }
}
