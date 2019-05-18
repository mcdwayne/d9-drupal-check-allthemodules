<?php

namespace Drupal\Tests\entity_pilot\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\entity_pilot\AuthenticationInterface;
use Drupal\entity_pilot\Data\FlightManifestInterface;
use Drupal\entity_pilot\Exception\TransportException;
use Drupal\entity_pilot\Transport;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests Transport service.
 *
 * @group entity_pilot
 * @group larowlan
 * @coversDefaultClass \Drupal\entity_pilot\Transport
 */
class TransportTest extends UnitTestCase {

  /**
   * Authentication service.
   *
   * @var \Drupal\entity_pilot\AuthenticationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $authentication;

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $client;

  /**
   * Serializer.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $serializer;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->authentication = $this->createMock(AuthenticationInterface::class);
    $this->client = $this->createMock(ClientInterface::class);
    $this->serializer = new Json();
  }

  /**
   * Tests when 429 over quota is returned.
   *
   * @covers ::sendFlight
   */
  public function test429OverQuota() {
    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->any())
      ->method('getStatusCode')
      ->willReturn(429);
    $response->expects($this->any())
      ->method('getHeader')
      ->with('Retry-After')
      ->willReturn(['86400']);
    $exception = new RequestException('Over quota', $this->createMock(RequestInterface::class), $response);
    $this->client->expects($this->once())
      ->method('send')
      ->willThrowException($exception);
    $this->authentication->expects($this->once())
      ->method('sign')
      ->willReturnArgument(0);
    $transport = new Transport($this->serializer, $this->client, $this->authentication);
    try {
      $transport->sendFlight($this->createMock(FlightManifestInterface::class), 'ssh its a secret');
    }
    catch (TransportException $e) {
      $this->assertEquals(TransportException::QUOTA_EXCEEDED, $e->getCode());
      $this->assertEquals(sprintf('You are over your monthly quota, which resets on %s. Alternatively visit https://entitypilot.com and choose an alternate plan.', (new \DateTime())->modify('+ 1 day')->format('d-m-Y')), $e->getMessage());
      return;
    }
    $this->fail('Exception not thrown');
  }

}
