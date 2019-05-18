<?php

/**
 * @file
 * Contains \Drupal\Tests\apiservices\Unit\GuzzleResponseTest.
 */

namespace Drupal\Tests\apiservices\Unit;

use Drupal\apiservices\GuzzleResponse;
use Drupal\Tests\UnitTestCase;

/**
 * @group apiservices
 */
class GuzzleResponseTest extends UnitTestCase {

  /**
   * Create a mock HTTP response.
   *
   * @param string $body
   *   (optional) The response body.
   * @param array $headers
   *   (optional) The response headers, in PSR-7 format (values are also arrays).
   * @param int $code
   *   (optional) The response status code. Defaults to 200.
   * @param string $reason
   *   (optional) The response reason phrase. Defaults to 'OK'.
   * @param string $protocol
   *   (optional) The HTTP protocol version. Defaults to '1.1'.
   */
  protected function mockResponse($body = '', array $headers = [], $code = 200, $reason = 'OK', $protocol = '1.1') {
    $response = $this->prophesize('GuzzleHttp\Psr7\Response');
    // Technically this should return a stream.
    $response->getBody()->willReturn($body);
    $response->getHeaders()->willReturn($headers);
    $response->getStatusCode()->willReturn($code);
    $response->getReasonPhrase()->willReturn($reason);
    $response->getProtocolVersion()->willReturn($protocol);
    return $response->reveal();
  }

  /**
   * Tests that JSON responses are decoded.
   */
  public function testDecoding() {
    $data = ['hello' => 'world'];
    $encoded = json_encode($data);
    $response = new GuzzleResponse($this->mockResponse($encoded, ['Content-Type' => ['application/json']]));
    $this->assertEquals($data, $response->getBody());
    $this->assertEquals($encoded, $response->getBodyRaw());
    // Do not decode other content types, even if the body is json-encoded.
    $response = new GuzzleResponse($this->mockResponse($encoded, ['Content-Type' => ['text/html']]));
    $this->assertEquals($encoded, $response->getBody());
    // Should work with complex types.
    $response = new GuzzleResponse($this->mockResponse($encoded, ['Content-Type' => ['application/json; charset=utf-8']]));
    $this->assertEquals($data, $response->getBody());
  }

  /**
   * Tests that decoding malformed JSON results in an exception.
   *
   * @expectedException \Drupal\apiservices\Exception\EndpointException
   * @expectedExceptionMessage Unable to decode JSON response
   */
  public function testDecodingException() {
    $response = new GuzzleResponse($this->mockResponse('["test"', ['Content-Type' => ['application/json']]));
    $response->getBody();
  }

  /**
   * Tests that gzip compressed responses are decompressed.
   */
  public function testDecompression() {
    if (!extension_loaded('zlib')) {
      $this->markTestSkipped();
      return;
    }
    $body = gzencode(__METHOD__);
    $response = new GuzzleResponse($this->mockResponse($body, ['Content-Encoding' => ['gzip']]));
    $this->assertEquals(__METHOD__, $response->getBody());
    $this->assertEquals($body, $response->getBodyRaw());
  }

  /**
   * Tests that incorrectly compressed data throws an exception.
   *
   * @expectedException \Drupal\apiservices\Exception\EndpointException
   * @expectedExceptionMessage Unable to decode compressed response
   */
  public function testDecompressionException() {
    if (!extension_loaded('zlib')) {
      $this->markTestSkipped();
      return;
    }
    $response = new GuzzleResponse($this->mockResponse('', ['Content-Encoding' => ['gzip']]));
    $response->getBody();
  }

  /**
   * Tests the values of a response.
   */
  public function testResponse() {
    $date = date('r');
    $response = new GuzzleResponse($this->mockResponse('test', ['Date' => [$date]], 304, 'Not Modified'));
    $this->assertEquals('test', $response->getBody());
    $this->assertEquals($date, $response->getHeader('Date'));
    $this->assertEquals(304, $response->getStatusCode());
    $this->assertEquals('Not Modified', $response->getReason());
    $this->assertEquals('1.1', $response->getProtocol());
  }

}
