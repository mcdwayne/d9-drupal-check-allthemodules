<?php

namespace Drupal\Tests\alexanders\Functional;

use Drupal\alexanders\Entity\AlexandersOrder;
use Drupal\alexanders\Entity\AlexandersOrderItem;
use Drupal\alexanders\Entity\AlexandersShipment;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Exception\ClientException;

/**
 * Functional tests for Alexanders modules site endpoints.
 *
 * @group alexanders
 */
class AlexanderApiSelf extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'alexanders',
    'system',
    'datetime',
    'address',
  ];

  private $client;
  private $url;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $config = \Drupal::configFactory()->getEditable('alexanders.settings');
    $config->set('real_api_key', 'alex-functionaltests');
    $config->save();
    $this->client = \Drupal::httpClient([
      'X-API-KEY' => 'alex-functionaltests',
      'Content-Type' => 'application/json',
    ]);
    $this->url = $this->baseUrl . '/alexanders';
    AlexandersOrder::create([
      'order_number' => 1,
      'orderItems' => [
        AlexandersOrderItem::create([
          'sku' => $this->randomString(),
          'quantity' => 1,
          'file' => 'example.com',
          'foil' => 'example.com',
        ]),
      ],
      'shipping' => [
        AlexandersShipment::create([
          'method' => 'Test',
          'address' => [],
        ]),
      ],
    ])->save();
  }

  /**
   * Tests endpoints for a 200 OKAY.
   */
  public function testAlexandersSiteEndpoints() {
    // Verify we can't GET resources.
    try {
      $this->client->get($this->url . '/printing/1');
    }
    catch (ClientException $e) {
      self::assertNotEmpty($e);
    }
    $config = \Drupal::configFactory()->getEditable('alexanders.settings');
    self::assertEquals('alex-functionaltests', $config->get('real_api_key'));
    // Run through PUT endpoints.
    $response = $this->client->put($this->url . '/printing/1', ['body' => json_encode(['dueDate' => '2019-01-16T19:53:37.469Z'])]);
    self::assertEquals(200, $response->getStatusCode());
    $response = $this->client->put($this->url . '/shipped/1', []);
    self::assertEquals(200, $response->getStatusCode());
    $response = $this->client->put($this->url . '/error/1', ['body' => json_encode(['message' => 'test'])]);
    self::assertEquals(200, $response->getStatusCode());
  }

}
