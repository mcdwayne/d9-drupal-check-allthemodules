<?php

namespace Drupal\jg_leaderboard;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class LeaderboardAPI
 *
 * @package Drupal\jg_leaderboard
 */
class LeaderboardAPI {
  protected $eventId;
  protected $clienAPI;

  /**
   * LeaderboardAPI constructor.
   *
   * @param array $client
   * @param       $eventId
   */
  function __construct(array $client, $eventId) {
    $this->$eventId = $eventId;
    $this->clienAPI = new JGClient($client);
  }

  /**
   * @return mixed
   */
  public function getEventId() {
    return $this->eventId;
  }

  /**
   * @param $eventId
   *
   * @return mixed
   */
  public function getEvent($eventId) {
    $evetnUri = "https://api.justgiving.com/" . "{apiKey}/v{apiVersion}/event/" . $eventId;
    $url      = $this->clienAPI->buildUrl($evetnUri);

    return $url;
  }

  /**
   * Take an eventId of justgiving and for a given just giving envirnoment and
   * api key and return details associated with that event.
   *
   * @param $eventId
   *
   * @return mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function eventResponse($eventId) {
    $client = \Drupal::httpClient();

    $headers = [
      'headers' => [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json'
      ]
    ];

    $request  = $client->request('GET', $this->getEvent($eventId), $headers);
    $response = json_decode($request->getBody(), TRUE);

    return $response;
  }

  /**
   * @param     $eventId
   * @param int $pageNumber
   * @param int $pageSize
   *
   * @return mixed
   */
  public function getEventPagesUrl($eventId, $pageNumber = 1, $pageSize = 100) {
    //@todo use envirnoment dynamically
    $pagesUri = "https://api.justgiving.com/" . "{apiKey}/v{apiVersion}/event/" . $eventId . "/pages?page=" . $pageNumber . "&pagesize=" . $pageSize;
    $url      = $this->clienAPI->buildUrl($pagesUri);

    return $url;
  }

  /**
   * @param $eventId
   *
   * @return mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function eventPages($eventId) {
    $client = \Drupal::httpClient();

    $headers = [
      'headers' => [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json'
      ]
    ];

    // Add true arg - use this to show array as oppose to object.
    $request  = $client->request('GET', $this->getEventPagesUrl($eventId), $headers);
    $response = json_decode($request->getBody());

    return $response;
  }

  /**
   * Return response status code.
   *
   * @param $eventId
   *
   * @return int|mixed
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function eventStatusCode($eventId) {
    $headers = [
      'headers' => [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json'
      ]
    ];

    $client = \Drupal::httpClient();

    //@todo make the other part of the uri dynamic.
    $uri = $this->getEvent($eventId);

    // Catch exceptions.
    try {
      $request    = $client->request('GET', $uri, $headers);
      $statusCode = $request->getStatusCode();

      return $statusCode;
    } catch (RequestException $e) {

      return ($e->getCode());
    }
  }
}
