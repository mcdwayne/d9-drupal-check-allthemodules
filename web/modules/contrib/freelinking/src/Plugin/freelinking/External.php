<?php

namespace Drupal\freelinking\Plugin\freelinking;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\freelinking\Plugin\FreelinkingPluginBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Freelinking external link plugin.
 *
 * @Freelinking(
 *   id = "external",
 *   title = @Translation("External links"),
 *   weight = 0,
 *   hidden = false,
 *   settings = {
 *     "scrape" = "1"
 *   }
 * )
 *
 * @todo Should SSL links be a separate plugin?
 */
class External extends FreelinkingPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal Guzzle Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition array.
   * @param \GuzzleHttp\Client $client
   *   A configured HTTP Request client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $client) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndicator() {
    return '/^http(s)?|ext(ernal)?/';
  }

  /**
   * {@inheritdoc}
   */
  public function getTip() {
    return $this->t('Click to visit an external URL.');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['settings' => ['scrape' => '1']] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getConfiguration()['settings'];
    $element['scrape'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scrape external URLs'),
      '#description' => $this->t('Should freelinking try to scrape external URLs?'),
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => isset($settings['scrape']) ? $settings['scrape'] : '1',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLink(array $target) {
    $scrape = $this->getConfiguration()['settings']['scrape'];
    $scheme = preg_match('/^http(s)?$/', $target['indicator']) === 1 ? $target['indicator'] . ':' : '';
    $url = $scheme . $target['dest'];

    $link = [
      '#type' => 'link',
      '#url' => Url::fromUri($url, ['absolute' => TRUE, 'language' => $target['language']]),
      '#attributes' => [
        'title' => $this->getTip(),
      ],
    ];

    // Get the page title from the external URL or use the target text.
    if (!$target['text'] && $scrape) {
      try {
        $page_title = $this->getPageTitle($url);
        if ($page_title) {
          $link['#title'] = $this->t('Ext. link: “@title”', ['@title' => $page_title]);
        }
        else {
          $link['#title'] = $url;
        }
      }
      catch (RequestException $e) {
        $link = [
          '#theme' => 'freelink_error',
          '#plugin' => 'external',
        ];

        if ($e->getResponse()->getStatusCode() >= 400) {
          $link['#message'] = $this->t('External target “@url” not found', ['@url' => $url]);
        }
      }
    }
    else {
      $link['#title'] = $target['text'] ? $target['text'] : $target['dest'];
    }

    return $link;
  }

  /**
   * Get the page title by fetching from the external URL.
   *
   * @param string $url
   *   The URL to fetch.
   *
   * @return string
   *   The page title to use.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function getPageTitle($url) {
    // Try to fetch the URL.
    $response = $this->client->get($url);
    $body = $response->getBody()->getContents();

    // Extract the page title from either the h1 or h2.
    if (preg_match('/<h1.*>(.*)<\/h1>/', $body, $matches)) {
      if (strlen($matches[1]) < 3 && preg_match('/<h2.*>(.*)<\/h2>/', $body, $h2_matches)) {
        return $h2_matches[1];
      }
      return $matches[1];
    }

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

}
