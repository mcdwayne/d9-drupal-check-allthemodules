<?php

namespace Drupal\feeds_advance_crawler\Feeds\Fetcher\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;
use Drupal\feeds\Utility\Feed;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form on the feed edit page for the AdvanceCrawlerFetcher.
 */
class AdvanceCrawlerFetcherFeedForm extends ExternalPluginFormBase implements ContainerInjectionInterface {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Constructs an AffiliatesFetcherFeedForm object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed_config = $feed->getConfigurationFor($this->plugin);

    $form['source'] = [
      '#title' => $this->t('Feed URL'),
      '#type' => 'url',
      '#default_value' => $feed->getSource(),
      '#required' => TRUE,
    ];

    $form['feeds_crawler'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Feeds Crawler'),
      '#description' => $this->t('Enable feeds crawler'),
      '#default_value' => $feed_config['feeds_crawler'],
    ];

    $form['feeds_crawler_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Feeds Crawler Settings'),
      '#open' => TRUE,
      '#states' => [
        "visible" => [
          "input[name='plugin[fetcher][feeds_crawler]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['feeds_crawler_settings']['no_of_pages'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of pages'),
      '#default_value' => $feed_config['no_of_pages'],
      '#description' => $this->t('The number of pages to fetch.'),
      '#parents' => ['plugin', 'fetcher', 'no_of_pages'],
      '#size' => 60,
      '#maxlength' => 60,
      '#states' => [
        "required" => [
          "input[name='plugin[fetcher][feeds_crawler]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['feeds_crawler_settings']['delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delay'),
      '#default_value' => $feed_config['delay'],
      '#description' => $this->t('Number of seconds to delay in between fetches.'),
      '#parents' => ['plugin', 'fetcher', 'delay'],
      '#size' => 60,
      '#maxlength' => 60,
    ];

    $form['feeds_crawler_settings']['url_pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URL pattern'),
      '#default_value' => $feed_config['url_pattern'],
      '#description' => $this->t('A URL with the variable $index replaced with an increnting number. For example: http://example.com?page=$index.'),
      '#parents' => ['plugin', 'fetcher', 'url_pattern'],
      '#size' => 60,
      '#states' => [
        "required" => [
          "input[name='plugin[fetcher][feeds_crawler]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['feeds_crawler_settings']['initial_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial value of $index'),
      '#default_value' => $feed_config['initial_value'],
      '#description' => $this->t('The initial value of the $index variable.'),
      '#parents' => ['plugin', 'fetcher', 'initial_value'],
      '#size' => 60,
      '#maxlength' => 60,
      '#states' => [
        "required" => [
          "input[name='plugin[fetcher][feeds_crawler]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['feeds_crawler_settings']['increment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Increment $index by'),
      '#default_value' => $feed_config['increment'],
      '#description' => $this->t('The increment the value of $index variable.'),
      '#parents' => ['plugin', 'fetcher', 'increment'],
      '#size' => 60,
      '#maxlength' => 60,
    ];

    $form['inner_feeds_scraper'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Inner Scraping'),
      '#description' => $this->t('Enable inner page scraping'),
      '#default_value' => $feed_config['inner_feeds_scraper'],
    ];

    $form['inner_feeds_scraper_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Inner Page Scraping Settings'),
      '#open' => TRUE,
      '#states' => [
        "visible" => [
          "input[name='plugin[fetcher][inner_feeds_scraper]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['inner_feeds_scraper_settings']['context'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Context'),
      '#default_value' => $feed_config['context'],
      '#description' => $this->t('It should be equivalent to Context defined in mapping'),
      '#parents' => ['plugin', 'fetcher', 'context'],
      '#maxlength' => 1024,
      '#states' => [
        "required" => [
          "input[name='plugin[fetcher][inner_feeds_scraper]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['inner_feeds_scraper_settings']['link_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Selector'),
      '#default_value' => $feed_config['link_selector'],
      '#description' => $this->t('The link of the selector should be in reference to the contex in the mapping field.'),
      '#parents' => ['plugin', 'fetcher', 'link_selector'],
      '#maxlength' => 1024,
      '#states' => [
        "required" => [
          "input[name='plugin[fetcher][inner_feeds_scraper]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['inner_feeds_scraper_settings']['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#default_value' => $feed_config['base_url'],
      '#description' => $this->t('Base URL if not provided in the link'),
      '#parents' => ['plugin', 'fetcher', 'base_url'],
      '#maxlength' => 1024,
    ];

    $form['inner_feeds_scraper_settings']['inner_page_selector'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Page Selector'),
      '#default_value' => $feed_config['inner_page_selector'],
      '#description' => $this->t('Selector to be added of the inner page. For example: .container'),
      '#parents' => ['plugin', 'fetcher', 'inner_page_selector'],
      '#size' => 60,
      '#states' => [
        "required" => [
          "input[name='plugin[fetcher][inner_feeds_scraper]']" => ["checked" => TRUE],
        ],
      ],
    ];

    $form['inner_feeds_scraper_settings']['break_in_parts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Break fetching'),
      '#description' => $this->t('Scrape inner links in divisons/parts'),
      '#default_value' => $feed_config['break_in_parts'],
      '#parents' => ['plugin', 'fetcher', 'break_in_parts'],
    ];

    $form['inner_feeds_scraper_settings']['no_of_parts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Break fetching in no. of links'),
      '#default_value' => $feed_config['no_of_parts'],
      '#description' => $this->t('Maximum no. of links to be fetched per divisons'),
      '#parents' => ['plugin', 'fetcher', 'no_of_parts'],
      '#size' => 60,
      '#states' => [
        "required" => [
          "input[name='plugin[fetcher][break_in_parts]']" => ["checked" => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    try {
      $url = Feed::translateSchemes($form_state->getValue('source'));
    }
    catch (\InvalidArgumentException $e) {
      $form_state->setError($form['source'], $this->t("The url's scheme is not supported. Supported schemes are: @supported.", [
        '@supported' => implode(', ', Feed::getSupportedSchemes()),
      ]));
      // If the source doesn't have a valid scheme the rest of the validation
      // isn't helpful. Break out early.
      return;
    }
    $form_state->setValue('source', $url);

    try {
      $response = $this->client->get($url);
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      $form_state->setError($form['source'], $this->t('The feed from %site seems to be broken because of error "%error".', $args));

      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    $feed->setSource($form_state->getValue('source'));
    $feed_config = [
      'feeds_crawler' => $form_state->getValue('feeds_crawler'),
      'no_of_pages' => $form_state->getValue('no_of_pages'),
      'delay' => $form_state->getValue('delay'),
      'url_pattern' => $form_state->getValue('url_pattern'),
      'initial_value' => $form_state->getValue('initial_value'),
      'increment' => $form_state->getValue('increment'),
      'inner_feeds_scraper' => $form_state->getValue('inner_feeds_scraper'),
      'context' => $form_state->getValue('context'),
      'link_selector' => $form_state->getValue('link_selector'),
      'base_url' => $form_state->getValue('base_url'),
      'inner_page_selector' => $form_state->getValue('inner_page_selector'),
      'break_in_parts' => $form_state->getValue('break_in_parts'),
      'no_of_parts' => $form_state->getValue('no_of_parts'),
    ];
    $feed->setConfigurationFor($this->plugin, $feed_config);
  }

  /**
   * Performs a GET request.
   *
   * @param string $url
   *   The URL to GET.
   *
   * @return \Guzzle\Http\Message\Response
   *   A Guzzle response.
   *
   * @throws \RuntimeException
   *   Thrown if the GET request failed.
   */
  protected function get($url) {
    try {
      $response = $this->client->get(Feed::translateSchemes($url));
    }
    catch (RequestException $e) {
      $args = ['%site' => $url, '%error' => $e->getMessage()];
      throw new \RuntimeException($this->t('The feed from %site seems to be broken because of error "%error".', $args));
    }

    return $response;
  }
}
