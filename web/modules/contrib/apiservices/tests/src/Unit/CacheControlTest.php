<?php

/**
 * @file
 * Contains \Drupal\Tests\apiservices\Unit\CacheControlTest.
 */

namespace Drupal\Tests\apiservices\Unit;

use Drupal\apiservices\CacheControl;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\apiservices\CacheControl
 * @group apiservices
 */
class CacheControlTest extends UnitTestCase {

  /**
   * Creates a mock HTTP response.
   *
   * @param array $headers
   *   (optional) A list of header contained in the response, keyed by the
   *   header name.
   * @param int $status_code
   *   (optional) The response status code. Defaults to 200.
   */
  protected function mockResponse(array $headers = [], $status_code = 200) {
    $response = $this->prophesize('Psr\Http\Message\ResponseInterface');
    foreach ($headers as $header => $value) {
      if ($value === FALSE) {
        $response->hasHeader($header)->willReturn(FALSE);
      }
      else {
        $response->hasHeader($header)->willReturn(TRUE);
        if (is_array($value)) {
          $response->getHeader($header)->willReturn($value);
        }
        else {
          $response->getHeaderLine($header)->willReturn($value);
        }
      }
    }
    $response->getStatusCode()->willReturn($status_code);
    return $response->reveal();
  }

  /**
   * Tests if a response contains a cache constraint header.
   */
  public function testCacheConstraint() {
    $response = $this->mockResponse(['Cache-Control' => ['no-store'], 'Vary' => FALSE]);
    $this->assertTrue(CacheControl::hasCacheConstraint($response));
    $response = $this->mockResponse(['Cache-Control' => FALSE, 'Vary' => '*']);
    $this->assertTrue(CacheControl::hasCacheConstraint($response));
    $response = $this->mockResponse(['Cache-Control' => FALSE, 'Vary' => FALSE]);
    $this->assertFalse(CacheControl::hasCacheConstraint($response));
  }

  /**
   * Tests if the correct cache lifetime is obtained from a response.
   */
  public function testCacheLifetime() {
    $time = time();
    // Get the lifetime from the 'Cache-Control' header.
    $response = $this->mockResponse([
      'Date' => date(\DateTime::RFC2822, $time),
      'Cache-Control' => ['max-age=60'],
    ]);
    $this->assertEquals(60, CacheControl::getResponseLifetime($response));
    // Get the lifetime from the 'Expires' header.
    $response = $this->mockResponse([
      'Date' => date(\DateTime::RFC2822, $time),
      'Cache-Control' => FALSE,
      'Expires' => date(\DateTime::RFC2822, $time + 60),
    ]);
    $this->assertEquals(60, CacheControl::getResponseLifetime($response));
    // If both headers are present, the 'Cache-Control' header has priority.
    $response = $this->mockResponse([
      'Date' => date(\DateTime::RFC2822, $time),
      'Cache-Control' => ['max-age=60'],
      'Expires' => date(\DateTime::RFC2822, $time + 30),
    ]);
    $this->assertEquals(60, CacheControl::getResponseLifetime($response));
    // Check that an invalid 'Expires' header results in a time that is in the
    // past.
    $response = $this->mockResponse([
      'Date' => date(\DateTime::RFC2822, $time),
      'Cache-Control' => FALSE,
      'Expires' => '0',
    ]);
    $expire = CacheControl::getResponseLifetime($response);
    $this->assertTrue($expire < $time);
    // If no cache headers are present, no lifetime should be returned.
    $response = $this->mockResponse([
      'Date' => date(\DateTime::RFC2822, $time),
      'Cache-Control' => FALSE,
      'Expires' => FALSE,
    ]);
    $this->assertTrue(CacheControl::getResponseLifetime($response) === FALSE);
  }

  /**
   * Test that responses without a creation date cannot be cached (as it would
   * be impossible to determine their age).
   *
   * @expectedException \InvalidArgumentException
   */
  public function testCacheLifetimeMissingHeader() {
    CacheControl::getResponseLifetime($this->mockResponse(['Date' => FALSE]));
  }

  /**
   * Tests the cacheability of various requests.
   *
   * @dataProvider getRequestMethods
   */
  public function testRequestCacheability($method, $has_auth, $expected) {
    $request = $this->prophesize('Psr\Http\Message\RequestInterface');
    $request->getMethod()->willReturn($method);
    $request->hasHeader('Authorization')->willReturn($has_auth);
    $this->assertEquals($expected, CacheControl::isRequestCacheable($request->reveal()));
  }

  /**
   * Data provider; Gets request methods and if an 'Authorization' should be
   * tests.
   *
   * @see CacheControlTest::testRequestCacheability()
   */
  public function getRequestMethods() {
    return [
      ['GET', FALSE, TRUE],
      ['GET', TRUE, FALSE],
      ['HEAD', FALSE, TRUE],
      ['HEAD', TRUE, FALSE],
      ['POST', FALSE, FALSE],
      ['PUT', FALSE, FALSE],
      ['DELETE', FALSE, FALSE],
      ['TRACE', FALSE, FALSE],
      ['CONNECT', FALSE, FALSE],
    ];
  }

  /**
   * Tests the cacheability of various response status codes.
   *
   * @dataProvider getResponseCodes
   */
  public function testResponseCodeCacheability($status_code, $expected) {
    $response = $this->mockResponse([], $status_code);
    $this->assertEquals($expected, CacheControl::isResponseCodeCacheable($response));
  }

  /**
   * Data provider; Gets a list of status codes that are cacheable by default.
   *
   * @see CacheControlTest::testResponseCodeCacheability()
   *
   * @link https://tools.ietf.org/html/rfc7231#section-6.1
   */
  public function getResponseCodes() {
    return [
      [100, FALSE],
      [101, FALSE],
      [200, TRUE],  // OK
      [201, FALSE],
      [202, FALSE],
      [203, TRUE],  // Non-Authoritative Information
      [204, TRUE],  // No Content
      [205, FALSE],
      [206, TRUE],  // Partial Content
      [300, TRUE],  // Multiple Choices
      [301, TRUE],  // Moved Permanently
      [302, FALSE],
      [303, FALSE],
      [304, FALSE],
      [305, FALSE],
      [307, FALSE],
      [400, FALSE],
      [401, FALSE],
      [402, FALSE],
      [403, FALSE],
      [404, TRUE],  // Not Found
      [405, TRUE],  // Method Not Allowed
      [406, FALSE],
      [407, FALSE],
      [408, FALSE],
      [409, FALSE],
      [410, TRUE],  // Gone
      [411, FALSE],
      [412, FALSE],
      [413, FALSE],
      [414, TRUE],  // URI Too Long
      [415, FALSE],
      [416, FALSE],
      [417, FALSE],
      [500, FALSE],
      [501, TRUE],  // Not Implemented
      [502, FALSE],
      [503, FALSE],
      [504, FALSE],
      [505, FALSE],
    ];
  }

}
