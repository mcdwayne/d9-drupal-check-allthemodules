<?php

namespace Drupal\Tests\search_365\Unit\Serializer;

use Drupal\search_365\SearchResults\ResultSet;
use Drupal\search_365\Serializer\ResultSetSerializerFactory;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\search_365\Serializer\ResultSetSerializerFactory
 * @group search_365
 */
class ResultSetSerializerTest extends UnitTestCase {

  /**
   * Test deserializing json.
   */
  public function testDeserialize() {
    $serializer = ResultSetSerializerFactory::create();
    $jsonData = file_get_contents(__DIR__ . '/../../../fixtures/response.json');
    /** @var \Drupal\search_365\SearchResults\ResultSet $resultSet */
    $resultSet = $serializer->deserialize($jsonData, ResultSet::class, 'json');
    $this->assertNotNull($resultSet);

    $this->assertEquals(234, $resultSet->getResultsCount());

    $result = $resultSet->getResults()[0];

    $this->assertEquals("Course and subject information | University of Technology Sydney", $result->getTitle());

  }

}
