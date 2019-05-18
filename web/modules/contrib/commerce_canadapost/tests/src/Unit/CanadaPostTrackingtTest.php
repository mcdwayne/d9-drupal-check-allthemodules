<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

use CanadaPost\Exception\ClientException;
use CanadaPost\Tracking;
use Psr\Http\Message\RequestInterface;

/**
 * Class CanadaPostTrackingtTest.
 *
 * @coversDefaultClass \Drupal\commerce_canadapost\Api\TrackingService
 * @group commerce_canadapost
 */
class CanadaPostTrackingtTest extends CanadaPostUnitTestBase {

  /**
   * ::covers fetchTrackingSummary.
   */
  public function testFetchTrackingSummaryWithTrackingPin() {
    // Technically, it's not good practice to mock the class we test, however,
    // we need to mock the getRequest() function and this is the only way to do
    // so.
    $tracking_service = $this->getMockBuilder('Drupal\commerce_canadapost\Api\TrackingService')
      ->setConstructorArgs([$this->loggerFactory, $this->utilities])
      ->setMethods(['getRequest'])
      ->getMock();

    // Tell the 'getRequest' method to return our mock request.
    $request = $this->getMockRequest();
    $tracking_service->method('getRequest')->willReturn($request);

    // Now, test that the function has successfully returned rates.
    $tracking_summary = $tracking_service->fetchTrackingSummary('7023210039414604', $this->shipment);

    $this->assertNotNull($tracking_summary);
    $this->assertArrayHasKey('pin', $tracking_summary);
    $this->assertEquals($tracking_summary['pin'], '7023210039414604');
    $this->assertEquals($tracking_summary['origin-postal-id'], 'K1G');
    $this->assertEquals($tracking_summary['destination-postal-id'], 'K0J');
    $this->assertEquals($tracking_summary['service-name'], 'Expedited Parcels');
    $this->assertEquals($tracking_summary['mailed-on-date'], '2011-04-04');
    $this->assertEquals($tracking_summary['expected-delivery-date'], '2011-04-05');
    $this->assertEquals($tracking_summary['actual-delivery-date'], []);
  }

  /**
   * ::covers fetchTrackingSummary.
   */
  public function testFetchTrackingSummaryWithoutTrackingPin() {
    // Technically, it's not good practice to mock the class we test, however,
    // we need to mock the getRequest() function and this is the only way to do
    // so.
    $tracking_service = $this->getMockBuilder('Drupal\commerce_canadapost\Api\TrackingService')
      ->setConstructorArgs([$this->loggerFactory, $this->utilities])
      ->setMethods(['getRequest'])
      ->getMock();

    // Tell the 'getRequest' method to return our mock request.
    $request = $this->getMockRequest('without_tracking_summary');
    $tracking_service->method('getRequest')->willReturn($request);

    // Now, test that the function has successfully returned rates.
    $tracking_summary = $tracking_service->fetchTrackingSummary('7023210039414604', $this->shipment);

    $this->assertEquals($tracking_summary, []);
  }

  /**
   * ::covers fetchTrackingSummary.
   */
  public function testFetchTrackingSummaryWithException() {
    // Technically, it's not good practice to mock the class we test, however,
    // we need to mock the getRequest() function and this is the only way to do
    // so.
    $tracking_service = $this->getMockBuilder('Drupal\commerce_canadapost\Api\TrackingService')
      ->setConstructorArgs([$this->loggerFactory, $this->utilities])
      ->setMethods(['getRequest'])
      ->getMock();

    // Tell the 'getRequest' method to return our mock request.
    $request = $this->getMockRequest('failure');
    $tracking_service->method('getRequest')->willReturn($request);

    // Now, test that the function has successfully returned rates.
    $tracking_summary = $tracking_service->fetchTrackingSummary('7023210039414604', $this->shipment);

    $this->assertEquals($tracking_summary, []);
  }

  /**
   * Creates a mock Canada Post tracking request service class.
   *
   * @param string $set_request_status
   *   Whether we want the return request to be an exception or a success.
   *
   * @return \CanadaPost\Tracking
   *   The mock Canada Post tracking request service object.
   */
  protected function getMockRequest($set_request_status = 'success') {
    // Create the mock response that we will set for the getRates() function.
    $request = $this->prophesize(Tracking::class);
    if ($set_request_status == 'failure') {
      $request_interface = $this->prophesize(RequestInterface::class);
      $request
        ->getSummary('7023210039414604')
        ->willThrow(new ClientException(
          'Exception!',
          'responseBody',
          $request_interface->reveal()
        ));
    }
    else {
      $response = $this->getMockResponse();

      // Remove the tracking-summary key if we are testing a response w/o it.
      if ($set_request_status == 'without_tracking_summary') {
        unset($response['tracking-summary']);
      }
      $request
        ->getSummary('7023210039414604')
        ->willReturn($response);
    }

    return $request->reveal();
  }

  /**
   * Returns a mock response.
   *
   * @return array
   *   An array with the tracking pin summary.
   */
  protected function getMockResponse() {
    $xml = simplexml_load_file(__DIR__ . '/../Mocks/tracking-response-success.xml');
    return ['tracking-summary' => json_decode(json_encode((array) $xml), TRUE)];
  }

}
