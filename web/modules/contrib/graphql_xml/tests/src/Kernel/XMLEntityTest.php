<?php

namespace Drupal\Tests\graphql_xml\Kernel;


use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\graphql\Kernel\GraphQLFileTestBase;
use Drupal\Tests\graphql_core\Kernel\GraphQLContentTestBase;
use Drupal\user\Entity\Role;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Test loading entities from xml.
 *
 * @group graphql_xml
 */
class XMLEntityTest extends GraphQLContentTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'graphql_xml',
  ];

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts() {
    return array_merge([
      'user.node_grants:view',
    ], parent::defaultCacheContexts());
  }

  /**
   * Test loading entities from json.
   */
  public function testXMLEntity() {
    $httpClient = $this->prophesize(ClientInterface::class);
    $httpClient
      ->request('GET', 'http://graphql.drupal/xml')
      ->willReturn(new Response(200, [], '<test><a data-uuid="abc"></a></test>'));
    $this->container->set('http_client', $httpClient->reveal());

    $entityRepository = $this->prophesize(EntityRepositoryInterface::class);
    $entityRepository->loadEntityByUuid('node', 'abc')->willReturn(Node::create([
      'uuid' => 'abc',
      'type' => 'article',
      'status' => 1,
    ]));
    $this->container->set('entity.repository', $entityRepository->reveal());

    $query = $this->getQueryFromFile('entity.gql');

    $this->assertResults($query, [], [
      'route' => [
        'request' => [
          'xml' => [
            'node' => [
              ['uuid' => 'abc'],
            ],
          ],
        ],
      ],
    ], $this->defaultCacheMetaData());
  }

}
