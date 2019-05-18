<?php

namespace Drupal\cision_feed\Result;

use Drupal\feeds\Result\FetcherResult;
use Drupal\feeds\FeedInterface;
use GuzzleHttp\Client;

/**
 * A fetcher result object.
 */
class CisionFetcherResult extends FetcherResult {

  /**
   * The raw string.
   *
   * @var string
   */
  protected $raw = '';

  /**
   * Configuration information.
   *
   * @var array
   */
  protected $config;

  /**
   * Constructs a CisionFetcherResult object.
   *
   * @param \FeedInterface $feed
   *   The feed fetcher source.
   * @param array $config
   *   The settings for the fetcher.
   */
  public function __construct(FeedInterface $feed, array $config) {
    $this->config = $config;

    $query_args = http_build_query([
      'pageSize' => $this->config['page_size'],
      'pageIndex' => $this->config['page_index'],
      'startDate' => $this->config['start_date'],
      'endDate' => $this->config['end_date'],
      'detailLevel' => 'detail',
      'format' => 'json',
    ]);

    $client = new Client();
    $response = $client->request('GET', $feed->getSource() . '?' . $query_args);
    $response = (string) $response->getBody();
    $feed_base = json_decode($response);
    if ($feed_base) {
      $this->filterFeed($feed_base);
      $this->handleTranslation($feed_base);
      $this->raw = json_encode($feed_base);
    }
  }

  /**
   * Finds a release based on its id.
   *
   * @param \stdClass $data
   *   An object with all feeds.
   * @param string $releaseId
   *   Id of the release to search for.
   *
   * @return \stdClass
   *   The release with the supplied id.
   */
  private function getReleaseById(\stdClass $data, $releaseId) {
    if (is_object($data) && isset($data->Releases)) {
      foreach ($data->Releases as $release) {
        if ($release->Id == $releaseId) {
          return $release;
        }
      }
    }
  }

  /**
   * Sets the tid for all LanguageVersions of an item.
   *
   * @param \stdClass $feed_base
   *   The base feed.
   */
  private function handleTranslation(\stdClass $feed_base) {
    foreach ($feed_base->Releases as $feed_item) {
      if (!isset($feed_item->Tid)) {
        $feed_item->Tid = $feed_item->Id;
      }
      if (count($feed_item->LanguageVersions)) {
        foreach ($feed_item->LanguageVersions as $version) {
          $translation = $this->getReleaseById($feed_base, $version->ReleaseId);
          if ($translation) {
            $translation->Tid = $feed_item->Id;
          }
        }
      }
    }
  }

  /**
   * Filters the feed releases.
   *
   * Removes any language versions from all releases not in
   * the selected language. This function also removes all
   * feeds that is not of the selected InformationType.
   *
   * @param \stdClass $feed
   *   The base feed.
   */
  private function filterFeed(\stdClass $feed) {
    foreach ($feed->Releases as $index => $release) {
      if (count($release->LanguageVersions)) {
        if ($release->LanguageCode != $this->config['language']) {
          $release->LanguageVersions = [];
        }
      }

      if (!in_array($release->InformationType, $this->config['types'])) {
        unset($feed->Releases[$index]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return $this->sanitizeRaw($this->raw);
  }

}
