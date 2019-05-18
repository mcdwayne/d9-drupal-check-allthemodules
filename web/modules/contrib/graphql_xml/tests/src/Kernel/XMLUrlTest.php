<?php

namespace Drupal\Tests\graphql_xml\Kernel;

use Drupal\Tests\graphql_core\Kernel\GraphQLCoreTestBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Test xml data from urls.
 *
 * @group graphql_xml
 */
class XMLUrlTest extends GraphQLCoreTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'graphql_xml',
  ];

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheTags() {
    return [
      'entity_field_info',
      'entity_types',
      'graphql',
      'graphql_response',
    ];
  }

  /**
   * Test xml response.
   */
  public function testXMLResponse() {
    $httpClient = $this->prophesize(ClientInterface::class);

    $httpClient
      ->request('GET', 'http://graphql.drupal/xml')
      ->willReturn(new Response(200, [], '<test><a>Test</a></test>'));

    $this->container->set('http_client', $httpClient->reveal());

    $query = $this->getQueryFromFile('url.gql');

    $this->assertResults($query, [], [
      'route' => [
        'request' => [
          'xml' => [
            'xpath' => [
              ['content' => 'Test'],
            ],
          ],
        ],
      ],
    ], $this->defaultCacheMetaData());
  }

  /**
   * Test nested xml responses.
   */
  public function testNestedXMLResponse() {
    $httpClient = $this->prophesize(ClientInterface::class);

    $httpClient
      ->request('GET', 'http://graphql.drupal/xml')
      ->willReturn(new Response(200, [], '<test><a href="http://graphql.drupal/xml/sub">Test</a></test>'));

    $httpClient
      ->request('GET', 'http://graphql.drupal/xml/sub')
      ->willReturn(new Response(200, [], '<sub>Subtest</sub>'));

    $this->container->set('http_client', $httpClient->reveal());

    $query = $this->getQueryFromFile('nested_url.gql');

    $this->assertResults($query, [], [
      'route' => [
        'request' => [
          'xml' => [
            'url' => [
              [
                'request' => [
                  'xml' => [
                    'content' => 'Subtest',
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ], $this->defaultCacheMetaData());
  }

}
