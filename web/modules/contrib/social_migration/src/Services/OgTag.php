<?php

namespace Drupal\social_migration\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OgTag Class.
 *
 * Provides the ability to grab OpenGraph tags from a remote URL.
 */
class OgTag {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
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
   * Get OpenGraph tags.
   *
   * @param string $url
   *   The URL to retrieve.
   * @param string $schema
   *   (Optional) The OpenGraph schema. Defaults to 'og' for OpenGraph tags.
   * @param string $tagName
   *   (Optional) The name of the tag to retrieve. If omitted, will retrieve all
   *   tags from the given schema.
   *
   * @return array
   *   The tags that match the request.
   */
  public function getTags($url, $schema = NULL, $tagName = NULL) {
    $schema = $schema ?: 'og';
    $tagFilter = $schema . ':' . $tagName;

    try {
      $response = $this->httpClient->get($url);
    }
    catch (RequestException $e) {
      // TODO: handle request errors.
      return [];
    }

    $crawler = new Crawler();
    $crawler->addHtmlContent($response->getBody());

    $ogTags = $crawler->filter("meta[property^='$tagFilter']");

    $props = [];
    $ogTags->each(function (Crawler $tag, $i) use (&$props) {
      $props[$tag->attr('property')] = $tag->attr('content');
    });

    return $props;
  }

}
