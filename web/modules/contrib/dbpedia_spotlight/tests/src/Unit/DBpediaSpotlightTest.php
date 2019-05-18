<?php

namespace Drupal\Tests\dbpedia_spotlight\Unit;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\dbpedia_spotlight\DbpediaSpotlightService;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;

/**
 *
 * @group dbpedia_spotlight
 */

class DBpediaSpotlightTest extends UnitTestCase {


  public function testEndpointService() {

    $http_client = new Client();

    $json = new Json();

    $service =new DbpediaSpotlightService($http_client, $json);

    $text = "President Obama called Wednesday on Congress to extend a tax for students included in last year's economic stimulus package, arguing that the policy provides more generous assistance. \n";

    $result = $service->dbpedia_spotlight_service($text);

    $this->assertNotNull($result);

  }


}



