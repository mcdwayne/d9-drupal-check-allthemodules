<?php

namespace Drupal\wikipedia_client;

use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WikipediaClient {

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $http_client;

  /**
   * Construct a Wikipedia client object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client) {
    $this->http_client = $http_client;
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
   * Retrieve and decode the response.
   *
   * @param string $string
   *   A page title to seach for.
   *
   * @return array
   *   A decoded array of data about the page.
   *
   * @see https://www.mediawiki.org/wiki/API:Query
   * @see http://docs.guzzlephp.org/en/latest/quickstart.html
   */
  public function getResponse($string) {

    $uri = 'https://en.wikipedia.org/w/api.php';
    $props = ['extracts'];
    $query = [
      'action' => 'query',
      'format' => 'json',
      'prop' => implode('|', $props),
      'exintro' => '',
      'titles' => $string,
    ];
    $options = ['query' => $query, 'http_errors' => FALSE];
    try {
      if ($response = $this->http_client->request('GET', $uri, $options)) {
        if ($data = $response->getBody()->getContents()) {
          $data = json_decode($data, TRUE);
          if (array_key_exists('query', $data) && array_key_exists('pages', $data['query'])) {
            return array_shift($data['query']['pages']);
          }
        }
      }
    }
    catch (RequestException $e) {
      watchdog_exception('wikipedia_client', $e);
    }
    return FALSE;
  }

  /**
   * Helper to clean up the markup that is returned.
   */
  public function clean($markup) {
    return trim(str_replace('<p>&nbsp;</p>', '', $markup));
  }

  /**
   * Helper to get the extract of the Wikipedia page.
   */
  public function getExtract($wiki_data) {
    if (array_key_exists('extract', $wiki_data)) {
      $extract = $wiki_data['extract'];
      $extract = $this->clean($extract);
      return $extract;
    }
    return '';
  }

  /**
   * Helper to get the link to the Wikipedia page.
   */
  public function getLink($wiki_data) {
    if (array_key_exists('title', $wiki_data)) {
      $url = Url::fromUri('https://en.wikipedia.org/wiki/' . $wiki_data['title']);
      $title = t($wiki_data['title']);
      return Link::fromTextAndUrl($title, $url)->toString();
    }
    return '';
  }

  /**
   * Helper to get the marked up extract and link from a Wikipedia page.
   */
  public function getMarkup($wiki_data) {
    return '<div class="wikipedia-data">' .
      $this->getExtract($wiki_data) .
        '<div class="wikipedia-link">Wikipedia: ' .
        $this->getLink($wiki_data) .
        '</div></div>';
  }
}
