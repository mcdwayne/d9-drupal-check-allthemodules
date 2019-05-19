<?php

namespace Drupal\warmer_cdn\Plugin\warmer;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\warmer\Plugin\WarmerPluginBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The cache warmer for the built-in entity cache.
 *
 * @Warmer(
 *   id = "cdn",
 *   label = @Translation("CDN"),
 *   description = @Translation("Executes HTTP requests to warm the edge caches. It is useful without a CDN as well, as it will also warm Varnish and Page Cache.")
 * )
 */
final class CdnWarmer extends WarmerPluginBase {

  use UserInputParserTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    assert($instance instanceof CdnWarmer);
    $instance->setHttpClient($container->get('http_client'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = []) {
    // Ensure items are fully loaded URLs.
    $urls = array_map([$this, 'resolveUri'], $ids);
    return array_filter($urls, [UrlHelper::class, 'isValid']);
  }

  /**
   * {@inheritdoc}
   */
  public function warmMultiple(array $items = []) {
    $headers = $this->parseHeaders();
    $responses = array_map(function ($url) use ($headers) {
      try {
        return $this->httpClient->request('GET', $url, ['headers' => $headers]);
      }
      catch (ClientException $exception) {
        return $exception->getResponse();
      }
      catch (RequestException $exception) {
        return $exception->getResponse();
      }
    }, $items);
    $responses = array_filter($responses, function ($res) {
      return $res instanceof ResponseInterface;
    });
    $successful = array_filter($responses, function (ResponseInterface $res) {
      return $res->getStatusCode() < 399;
    });
    return count($successful);
  }

  /**
   * Parses the configuration to extract the headers to inject in every request.
   *
   * @return array
   *   The array of headers as expected by Guzzle.
   */
  private function parseHeaders() {
    $configuration = $this->getConfiguration();
    $header_lines = $configuration['headers'];
    // Parse headers.
    return array_reduce($header_lines, function ($carry, $header_line) {
      list($name, $value_line) = array_map('trim', explode(':', $header_line));
      $values = array_map('trim', explode(';', $value_line));
      $values = array_filter($values);
      $values = count($values) === 1 ? reset($values) : $values;
      $carry[$name] = $values;
      return $carry;
    }, []);
  }

  /**
   * {@inheritdoc}
   */
  public function buildIdsBatch($cursor) {
    // Parse the sitemaps and extract the URLs.
    $config = $this->getConfiguration();
    $urls = empty($config['urls']) ? [] : $config['urls'];
    $cursor_position = is_null($cursor) ? -1 : array_search($cursor, $urls);
    if ($cursor_position === FALSE) {
      return [];
    }
    return array_slice($urls, $cursor_position + 1, (int) $this->getBatchSize());
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $this->validateHeaders($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function addMoreConfigurationFormElements(array $form, SubformStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URLs'),
      '#description' => $this->t('Enter the list of URLs. One on each line. Examples: https://example.org/foo/bar, /foo/bar.'),
      '#default_value' => empty($configuration['urls']) ? '' : implode("\n", $configuration['urls']),
    ];
    $form['headers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Headers'),
      '#description' => $this->t('Specific headers to use when making HTTP requests. Format: <code>Header-Name: value1; value2</code>'),
      '#default_value' => empty($configuration['headers']) ? '' : implode("\n", $configuration['headers']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues() + $this->configuration;
    $configuration['urls'] = $this->extractTextarea($configuration, 'urls');
    $configuration['headers'] = $this->extractTextarea($configuration, 'headers');
    $this->setConfiguration($configuration);
  }

  /**
   * Set the HTTP client.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The client.
   */
  public function setHttpClient(ClientInterface $client) {
    $this->httpClient = $client;
  }

}
