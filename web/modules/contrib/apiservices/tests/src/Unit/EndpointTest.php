<?php

/**
 * @file
 * Contains \Drupal\Tests\apiservices\Unit\EndpointTest.
 */

namespace Drupal\Tests\apiservices\Unit;

use Drupal\apiservices\Entity\Endpoint;
use Drupal\Tests\UnitTestCase;

/**
 * @group apiservices
 */
class EndpointTest extends UnitTestCase {

  /**
   * Tests the values of an API endpoint entity.
   */
  public function testEndpoint() {
    $values = [
      'id' => 'test',
      'arguments' => ['arg'],
      'name' => 'Test Endpoint',
      'path' => '/path/{arg}',
      'provider' => 'Drupal\apiservices\Example',
      'query' => ['param' => []],
    ];
    $endpoint = new Endpoint($values, 'apiservices_endpoint');
    $this->assertEquals($values['id'], $endpoint->id());
    $this->assertEquals($values['arguments'], $endpoint->getArguments());
    $this->assertEquals($values['name'], $endpoint->getName());
    $this->assertEquals($values['path'], $endpoint->getPath());
    $this->assertEquals($values['provider'], $endpoint->getProvider());
    $this->assertEquals($values['query'], $endpoint->getQueryParameters());
  }

}
