<?php

namespace Drupal\prefetcher\Plugin\PrefetcherCrawler;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\prefetcher\CrawlerInterface;
use Drupal\prefetcher\Entity\PrefetcherUriInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CrawlerBasic.
 *
 * @package Drupal\prefetcher
 *
 * @Crawler(
 *   id = "prefetcher_crawler_basic",
 *   label = @Translation("Basic Crawler"),
 * )
 */
class CrawlerBasic extends PluginBase implements CrawlerInterface {

  /**
   * Prefetcher configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $configuration;

  /**
   * Http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Request options for to initialize the Http client.
   *
   * @var array $requestOptions
   */
  protected $requestOptions;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a CrawlerBasic object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($config_factory->get('prefetcher.settings'));

    $this->buildRequestOptions();
    $this->client = new Client($this->requestOptions);
  }

  /**
   * Builds the request options array.
   */
  protected function buildRequestOptions() {
    $this->requestOptions = [];
    $crawler_config = $this->getCrawlerConfiguration();
    if (isset($crawler_config['allow_redirects'])) {
      $this->requestOptions['allow_redirects'] = (bool) $crawler_config['allow_redirects'];
    }
    if (isset($crawler_config['connect_timeout'])) {
      $this->requestOptions['connect_timeout'] = (int) $crawler_config['connect_timeout'];
    }
    if (isset($crawler_config['read_timeout'])) {
      $this->requestOptions['read_timeout'] = (int) $crawler_config['read_timeout'];
    }
    if (isset($crawler_config['timeout'])) {
      $this->requestOptions['timeout'] = (int) $crawler_config['timeout'];
    }
    if (isset($crawler_config['verify'])) {
      $this->requestOptions['verify'] = (bool) $crawler_config['verify'];
    }
  }

  public function crawl(PrefetcherUriInterface $prefetcher_uri) {
    return $this->crawlMultiple([$prefetcher_uri]);
  }

  public function crawlMultiple(array $prefetcher_uris) {
    $uris = $this->buildUriPool($prefetcher_uris);
    return $this->crawlPool('GET', $uris);
  }

  protected function handleResponse(ResponseInterface $response, PrefetcherUriInterface $prefetcherUri) {
    $date = new \DateTime('now');
    $prefetcherUri->set('last_crawled', $date->format('Y-m-d\TH:i:s'));
    if (isset($response->getHeader('Expires')[0])) {
      $expires_date = \DateTime::createFromFormat('D, d M Y H:i:s \G\M\T', $response->getHeader('Expires')[0], new \DateTimeZone('UTC'));
      if ($expires_date) {
        $expires_date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $prefetcherUri->set('expires', $expires_date->format('Y-m-d\TH:i:s'));
      }
      else {
        $prefetcherUri->set('expires', NULL);
      }
    }
    else {
      $prefetcherUri->set('expires', NULL);
    }

    if (isset($response->getHeader('Cache-Control')[0])) {
      $cache_control = $response->getHeader('Cache-Control');
      foreach ($cache_control as $value) {
        if (preg_match('/max\-age=(\d+)/', $value, $matches)) {
          $ttl = (int) $matches[1];
          $now = new \DateTime('now');
          $now->add(new \DateInterval("PT" . $ttl . "S"));
          $prefetcherUri->set('expires', $now->format('Y-m-d\TH:i:s'));
          break;
        }
      }
    }

    // If the server returns a special 'X-Drupal-Prefetcher-Expire' with a unix
    // timestamp value, we use this to set the 'expires' value. This has
    // priority over any Cache-Control max-age value returned by the server.
    // Note: we are making the assumption that the unix timestamp returned by
    // the server is using the UTC timezone.
    if (isset($response->getHeader('X-Drupal-Prefetcher-Expire')[0])) {
      $prefetcher_expire = $response->getHeader('X-Drupal-Prefetcher-Expire')[0];
      // stackoverflow.com/questions/2524680/check-whether-the-string-is-a-unix-timestamp
      if ((string) (int) $prefetcher_expire === (string) $prefetcher_expire) {
        $expires = new \DateTime();
        $expires->setTimezone(new \DateTimeZone("UTC"));
        $expires->setTimestamp($prefetcher_expire);
        // We save the time using the current site's timezone.
        $site_timezone = date_default_timezone_get();
        $expires->setTimezone(new \DateTimeZone($site_timezone));
        $prefetcherUri->set('expires', $expires->format('Y-m-d\TH:i:s'));
      }
    }

    if (empty($prefetcherUri->get('response_info'))) {
      $prefetcherUri->set('response_info', []);
    }
    $prefetcherUri->set('response_info', $prefetcherUri->get('response_info')[]=[
      'http_status' => $response->getStatusCode(),
      'reason' => $response->getReasonPhrase(),
      'response_host' => $response->getHeader('Host'),
    ]);
    $prefetcherUri->set('last_response_code', $response->getStatusCode());
    $prefetcherUri->set('last_response_size', $response->getBody()->getSize());
    // Succeeded, thus reset the number of tries.
    $prefetcherUri->set('tries', 0);
    // Successfully processed uri is considered active.
    $prefetcherUri->set('status', 1);
    $prefetcherUri->save();
  }

  protected function handleFail(RequestException $reason, PrefetcherUriInterface $prefetcherUri) {
    $date = new \DateTime('now');
    $prefetcherUri->set('last_crawled', $date->format('Y-m-d\TH:i:s'));
    $date->add(new \DateInterval('PT86400S'));
    // Try again, but not today.
    $prefetcherUri->set('expires', $date->format('Y-m-d\TH:i:s'));
    $tries = (int) $prefetcherUri->get('tries')->get(0)->value;
    $tries++;
    $prefetcherUri->set('tries', $tries);

    if ($tries >= $this->getConfiguration()->get('retry_threshold')) {
      $prefetcherUri->set('status', 0);
    }

    if (empty($prefetcherUri->get('response_info'))) {
      $prefetcherUri->set('response_info', []);
    }
    // Append new response info to existing response_info (if any).
    $prefetcherUri->set('response_info', $prefetcherUri->get('response_info')[]=[
      'http_status' => '0',
      'reason' => $reason->getMessage(),
      'request_host' => $reason->getRequest()->getHeader('Host'),
      'request_uri' => (string)$reason->getRequest()->getUri(),
      'request_uri_host' => (string)$reason->getRequest()->getUri()->getHost(),
      'request_uri_scheme' => (string)$reason->getRequest()->getUri()->getScheme(),
    ]);
    $prefetcherUri->set('last_response_code', 0);
    $prefetcherUri->save();
  }

  /**
   * Build pool of uris for given prefetcher uri.
   *
   * Creates URIs for given paths and configuration. If full uri is given adds
   * it to the pool.
   *
   * @param array $prefetcher_uris
   * @return array contains uri and prefetcher_uri entity
   * structure:
   * [
   *   [
   *     'uri' => 'URI',
   *     'entity' => $prefetcher_uri
   *   ],
   * ]
   */
  protected function buildUriPool(array $prefetcher_uris) {
    /**
     * structure:
     * [
     *   [
     *     'uri' => 'URI',
     *     'entity' => $prefetcher_uri
     *   ],
     * ]
     */
    $uris = [];
    foreach ($prefetcher_uris as $prefetcher_uri) {
      $uri = $prefetcher_uri->getUri();
      $prefetcher_uri->set('response_info', []);

      if (NULL === $uri) {
        // No uri given, get path and build uris.
        $path = $prefetcher_uri->getPath();
        foreach ($this->getConfiguration()->get('hosts') as $host) {
          $headers = [];
          if (!empty($host['http_header'])) {
            foreach ($host['http_header'] as $header) {
              $header = explode(': ', $header);
              $headers[$header[0]] = $header[1];
            }
          }
          if (!empty($host['auth']['use_auth'])) {
            $headers['Authorization'] = 'Basic ' . base64_encode($host['auth']['username'] . ':' . $host['auth']['password']);
          }
          foreach ($host['domains'] as $domain) {
            // Remove request modifying parameter because it can be done up-front.
            $headers['Host'] = $domain;
            $scheme = isset($host['scheme']) ? $host['scheme'] : 'http';
            $uri = $scheme .'://'. $host['host'] . $path;
            $uris[] = [
              'uri' => $uri,
              'hostname' => $domain,
              'scheme' => $scheme,
              'entity' => $prefetcher_uri,
              'headers' => $headers,
            ];
          }
        }
      }
      else {
        // Uri given.
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if (NULL === $scheme) {
          // Default to https if uri could not be extracted.
          $scheme = 'https';
        }
        $uris[] = [
          'uri' => $uri,
          'entity' => $prefetcher_uri,
          'scheme' => $scheme,
        ];
      }
    }
    return $uris;
  }

  /**
   * @param string                               $method  HTTP method
   * @param array                                $uris     Array with keys uri and entity @see ::buildUriPool().
   * @param array                                $headers Request headers
   * @param string|null|resource|StreamInterface $body    Request body
   * @param string                               $version Protocol version
   */
  protected function crawlPool($method, array $uris, array $headers = [], $body = null, $version = '1.1') {
    $config = $this->getCrawlerConfiguration();
    $concurrency_limit = !empty($config['concurrency']) ? (int) $config['concurrency'] : 10;
    $requests = function ($uris) use ($method, $headers, $body, $version) {
      foreach ($uris as $key => $uri_info) {
        if (!empty($uri_info['headers'])) {
          $headers = array_merge($headers, $uri_info['headers']);
        }

        $request = new Request($method, $uri_info['uri'], $headers, $body, $version);
        $request->getUri()->withScheme($uri_info['scheme']);
        yield $key => $request;
      }
    };

    $responses = [];

    $pool = new Pool($this->client, $requests($uris), [
      'concurrency' => $concurrency_limit,
      'fulfilled' => function ($response, $index) use ($uris, &$responses) {
        /**
         * @var Response $response
         */
        $this->handleResponse($response, $uris[$index]['entity']);
      },
      'rejected' => function ($reason, $index) use ($uris, &$responses) {
        $this->handleFail($reason, $uris[$index]['entity']);
      },
    ]);

    // Initiate the transfers and create a promise
    $promise = $pool->promise();

    // Force the pool of requests to complete.
    $promise->wait();
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, array $ajax_settings = []) {
    $element = [];
    /** @var \Drupal\Core\Config\ConfigBase $config */
    $config = $form_state->getFormObject()->getConfig();

    $crawler_settings = $config->get('crawler');
    $plugin_settings = $crawler_settings['plugin_settings'];
    $element['concurrency'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 100,
      '#title' => t('Limit of concurrent requests'),
      '#default_value' => !empty($plugin_settings['concurrency']) ? $plugin_settings['concurrency'] : 10,
      '#required' => TRUE,
    ];
    $element['connect_timeout'] = [
      '#type' => 'number',
      '#title' => t('Connection timeout in seconds'),
      '#default_value' => !empty($plugin_settings['connect_timeout']) ? $plugin_settings['connect_timeout'] : 10,
    ];
    $element['read_timeout'] = [
      '#type' => 'number',
      '#title' => t('Read timeout in seconds'),
      '#default_value' => !empty($plugin_settings['read_timeout']) ? $plugin_settings['read_timeout'] : 60,
    ];
    $element['timeout'] = [
      '#type' => 'number',
      '#title' => t('Overall timeout in seconds'),
      '#default_value' => !empty($plugin_settings['timeout']) ? $plugin_settings['timeout'] : 60,
    ];
    $element['allow_redirects'] = [
      '#type' => 'checkbox',
      '#title' => t('Following redirects is allowed'),
      '#default_value' => isset($plugin_settings['allow_redirects']) ? $plugin_settings['allow_redirects'] : FALSE,
    ];
    $element['verify'] = [
      '#type' => 'checkbox',
      '#title' => t('Verify SSL certificate'),
      '#default_value' => !empty($plugin_settings['verify']),
    ];
    $element['follow_links_xpath'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => t('Follow pager links (not used yet)'),
    ];
    $element['follow_links_xpath']['xpath'] = [
      '#type' => 'textfield',
      '#title' => 'xpath',
      '#default_value' => !empty($plugin_settings['follow_links_xpath']['xpath']) ? $plugin_settings['follow_links_xpath']['xpath'] : '',
    ];
    $element['follow_links_xpath']['limit'] = [
      '#type' => 'textfield',
      '#title' => 'Number of links to follow',
      '#default_value' => !empty($plugin_settings['follow_links_xpath']['limit']) ? $plugin_settings['follow_links_xpath']['limit'] : '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(Config $configuration) {
    $this->configuration = $configuration;
  }

  public function getHosts() {
    return $this->configuration->get('hosts');
  }

  public function getCrawlerConfiguration() {
    return $this->configuration->get('crawler')['plugin_settings'];
  }

}
