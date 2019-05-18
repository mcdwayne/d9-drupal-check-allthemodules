<?php

namespace Drupal\Tests\commerce_canadapost\Unit;

use CanadaPost\Exception\ClientException;
use CanadaPost\Rating;
use Psr\Http\Message\RequestInterface;

/**
 * Class CanadaPostRateRequestTest.
 *
 * @coversDefaultClass \Drupal\commerce_canadapost\Api\RatingService
 * @group commerce_canadapost
 */
class CanadaPostRateRequestTest extends CanadaPostUnitTestBase {

  /**
   * ::covers getRates.
   */
  public function testGetRatesWithPriceQuotes() {
    // Technically, it's not good practice to mock the class we test, however,
    // we need to mock the getRequest() function and this is the only way to do
    // so.
    $rating_service = $this->getMockBuilder('Drupal\commerce_canadapost\Api\RatingService')
      ->setConstructorArgs([$this->loggerFactory, $this->utilities])
      ->setMethods(['getRequest'])
      ->getMock();

    // Tell the 'getRequest' method to return our mock request.
    $request = $this->getMockRequest();
    $rating_service->method('getRequest')->willReturn($request);

    // Now, test that the function has successfully returned rates.
    $rates = $rating_service->getRates($this->shippingMethod, $this->shipment, []);

    $this->assertNotNull($rates);
    $this->assertCount(4, $rates);
    $expedited_rate = $rates[0];
    $this->assertEquals($expedited_rate->getId(), 'DOM.EP');
    $this->assertInstanceOf('Drupal\commerce_shipping\ShippingRate', $expedited_rate);
    $this->assertInstanceOf('Drupal\commerce_price\Price', $expedited_rate->getAmount());
    $this->assertEquals('10.21', $expedited_rate->getAmount()->getNumber());

    $this->assertEquals($rates[1]->getId(), 'DOM.PC');
    $this->assertEquals($rates[2]->getId(), 'DOM.RP');
    $this->assertEquals($rates[3]->getId(), 'DOM.XP');
  }

  /**
   * ::covers getRates.
   */
  public function testGetRatesWithoutPriceQuotes() {
    // Technically, it's not good practice to mock the class we test, however,
    // we need to mock the getRequest() function and this is the only way to do
    // so.
    $rating_service = $this->getMockBuilder('Drupal\commerce_canadapost\Api\RatingService')
      ->setConstructorArgs([$this->loggerFactory, $this->utilities])
      ->setMethods(['getRequest'])
      ->getMock();

    // Tell the 'getRequest' method to return our mock request.
    $request = $this->getMockRequest('without_price_quotes');
    $rating_service->method('getRequest')->willReturn($request);

    // Now, test that the function has successfully returned rates.
    $rates = $rating_service->getRates($this->shippingMethod, $this->shipment, []);

    $this->assertEquals($rates, []);
  }

  /**
   * ::covers getRates.
   */
  public function testGetRatesWithException() {
    // Technically, it's not good practice to mock the class we test, however,
    // we need to mock the getRequest() function and this is the only way to do
    // so.
    $rating_service = $this->getMockBuilder('Drupal\commerce_canadapost\Api\RatingService')
      ->setConstructorArgs([$this->loggerFactory, $this->utilities])
      ->setMethods(['getRequest'])
      ->getMock();

    // Tell the 'getRequest' method to return our mock request.
    $request = $this->getMockRequest('failure');
    $rating_service->method('getRequest')->willReturn($request);

    // Now, test that the exception works correctly.
    $rates = $rating_service->getRates($this->shippingMethod, $this->shipment, []);

    $this->assertEquals($rates, []);
  }

  /**
   * Creates a mock Canada Post request service class.
   *
   * @param string $set_request_status
   *   Whether we want the return request to be an exception or a success.
   *
   * @return \CanadaPost\Rating
   *   The mock Canada Post request service object.
   */
  protected function getMockRequest($set_request_status = 'success') {
    // Fetch the shipment details we need to send to the getRates() function.
    $order = $this->shipment->getOrder();
    $origin_postal_code = !empty($this->shippingMethod->getConfiguration()['shipping_information']['origin_postal_code'])
      ? $this->shippingMethod->getConfiguration()['shipping_information']['origin_postal_code']
      : $order->getStore()
        ->getAddress()
        ->getPostalCode();
    $postal_code = $this->shipment->getShippingProfile()
      ->get('address')
      ->first()
      ->getPostalCode();
    $weight = $this->shipment->getWeight()->convert('kg')->getNumber();

    // Create the mock response that we will set for the getRates() function.
    $request = $this->prophesize(Rating::class);
    if ($set_request_status == 'failure') {
      $request_interface = $this->prophesize(RequestInterface::class);
      $request
        ->getRates($origin_postal_code, $postal_code, $weight, [])
        ->willThrow(new ClientException(
          'Exception!',
          'responseBody',
          $request_interface->reveal()
        ));
    }
    else {
      $response = $this->getMockResponse();

      // Remove the price-quotes key if we are testing a response w/o price
      // quotes.
      if ($set_request_status == 'without_price_quotes') {
        unset($response['price-quotes']);
      }
      $request
        ->getRates($origin_postal_code, $postal_code, $weight, [])
        ->willReturn($response);
    }

    return $request->reveal();
  }

  /**
   * Returns a mock response.
   *
   * @return array
   *   An array of price quotes.
   */
  protected function getMockResponse() {
    $xml = simplexml_load_file(__DIR__ . '/../Mocks/rating-response-success.xml');
    return ['price-quotes' => json_decode(json_encode((array) $xml), TRUE)];
  }

}
