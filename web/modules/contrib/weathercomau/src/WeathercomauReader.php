<?php

/**
 * @file
 * Contains \Drupal\weathercomau\WeathercomauReader.
 */

namespace Drupal\weathercomau;

class WeathercomauReader implements WeathercomauReaderInterface {

  /**
   * The WeathercomauFetcher service.
   *
   * @var \Drupal\weathercomau\WeathercomauFetcherInterface
   */
  protected $weathercomauFetcher;

  /**
   * The WeathercomauParser service.
   *
   * @var \Drupal\weathercomau\WeathercomauParserInterface
   */
  protected $weathercomauParser;

  /**
   * Constructs a WeathercomauReader object.
   *
   * @param \Drupal\weathercomau\WeathercomauFetcherInterface $weathercomau_fetcher
   *   The WeathercomauParser fetcher service.
   * @param \Drupal\weathercomau\WeathercomauParserInterface $weathercomau_parser
   *   The Weather.com.au parser service.
   */
  public function __construct(WeathercomauFetcherInterface $weathercomau_fetcher, WeathercomauParserInterface $weathercomau_parser) {
    $this->weathercomauFetcher = $weathercomau_fetcher;
    $this->weathercomauParser = $weathercomau_parser;
  }

  /**
   * {@inheritdoc}
   */
  public function read($city, $state) {
    $data = array();

    $raw_data = $this->weathercomauFetcher->fetch($city, $state);
    if ($raw_data) {
      $parsed_data = $this->weathercomauParser->parse($raw_data);
      if ($parsed_data) {
        $data = $parsed_data;
      }
    }

    return $data;
  }

}
