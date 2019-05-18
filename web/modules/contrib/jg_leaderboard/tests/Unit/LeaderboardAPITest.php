<?php

namespace Drupal\Test\jg_leaderboard\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\jg_leaderboard\LeaderboardAPI;
use GuzzleHttp\Client;

/**
 * Class LeaderboardAPITest
 *
 * @package Drupal\Test\jg_leaderboard\Unit
 * @group jgleaderboard
 */
class LeaderboardAPITest extends UnitTestCase {
  protected $eventID = 4701546;

  // Test data.
  const CLIENT_DATA = [
    'envirnoment' => "https://api-sandbox.justgiving.com/",
    'api_key'     => "eaeb404c",
    'api_version' => "1",
  ];

  /**
   * Test to check for valid url to the JustGiving endpoint.
   */
  public function testGetEvent() {
    $events   = new LeaderboardAPI(self::CLIENT_DATA, $this->eventID);
    $eventURL = filter_var($events->getEvent($this->eventID), FILTER_VALIDATE_URL) ? TRUE : FALSE;
    $this->assertEquals(TRUE, $eventURL);
  }

  /**
   * Test event response i.e. if 200 OK if not then the resquest is not made
   * successfully.
   */
  public function testEventResponse() {
    $client = new Client();

    $headers = [
      'headers' => [
        'Accept'       => 'application/json',
        'Content-Type' => 'application/json'
      ]
    ];

    $events  = new LeaderboardAPI(self::CLIENT_DATA, $this->eventID);
    $request = $client->request('GET', $events->getEventPagesUrl($this->eventID), $headers);
    $this->assertEquals('200', $request->getStatusCode());
  }
}
