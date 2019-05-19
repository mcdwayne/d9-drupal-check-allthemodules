<?php

namespace Drupal\Tests\social_migration\Unit\services;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client as HttpClient;
use Drupal\social_migration\Services\OgTag;

/**
 * Tests OpenGraph retrieval.
 *
 * @group social_migration
 * @coversDefaultClass \Drupal\social_migration\Plugin\migrate\process\OgTag
 */
class OgTagTest extends UnitTestCase {

  /**
   * GuzzleHttp\Handler\MockHandler definition.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected $mockHandler;

  /**
   * Mocked GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $mockClient;

  /**
   * The body of the response to use in the mocked client.
   *
   * @var string
   */
  protected $body;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->mockHandler = new MockHandler();
    $handler = HandlerStack::create($this->mockHandler);
    $this->mockClient = new HttpClient(['handler' => $handler]);
  }

  /**
   * Test the getTags() method.
   *
   * @dataProvider provideTestCases
   */
  public function testGetTags($inputs, $response, $expected) {
    $this->mockHandler->append($response);
    $ogTag = new OgTag($this->mockClient);
    $actual = $ogTag->getTags($inputs['url'], $inputs['schema'], $inputs['tagName']);
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider for the testGetTags test.
   *
   * @return array
   *   The test cases to run.
   */
  public function provideTestCases() {
    $body = <<<BODY
<html>
  <head>
    <meta property="og:title" content="test title" />
    <meta property="og:type" content="basic_page" />
    <meta property="og:url" content="http://www.example.com/test.html" />
    <meta property="og:image" content="http://www.example.com/image.png" />
  </head>
  <body>
    <p>Test Body Content</p>
  </body>
</html>
BODY;

    $okResponse = new Response(200, [], $body);
    $notFoundResponse = new Response(404);

    return [
      'test get all tags' => [
        'inputs' => [
          'url' => 'http://www.example.com/test.html',
          'schema' => 'og',
          'tagName' => NULL,
        ],
        'response' => $okResponse,
        'expected' => [
          'og:title' => 'test title',
          'og:type' => 'basic_page',
          'og:url' => 'http://www.example.com/test.html',
          'og:image' => 'http://www.example.com/image.png',
        ],
      ],
      'test get single tag' => [
        'inputs' => [
          'url' => 'http://www.example.com/test.html',
          'schema' => 'og',
          'tagName' => 'image',
        ],
        'response' => $okResponse,
        'expected' => [
          'og:image' => 'http://www.example.com/image.png',
        ],
      ],
      'test getting a non-existant tag' => [
        'inputs' => [
          'url' => 'http://www.example.com/test.html',
          'schema' => 'og',
          'tagName' => 'description',
        ],
        'response' => $okResponse,
        'expected' => [],
      ],
      'test getting a 404 response' => [
        'inputs' => [
          'url' => 'http://www.example.com/test.html',
          'schema' => 'og',
          'tagName' => 'image',
        ],
        'response' => $notFoundResponse,
        'expected' => [],
      ],
    ];
  }

}
