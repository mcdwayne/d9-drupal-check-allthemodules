<?php

namespace Drupal\Tests\tracdelight\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\tracdelight\Entity\Product;
use Drupal\tracdelight\Tracdelight;
use GuzzleHttp\Client;


/**
 * @coversDefaultClass \Drupal\tracdelight\Tracdelight
 * @group tracdelight
 */
class TracdelightTest extends UnitTestCase {


  /**
   * The mock entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;


  protected $apiKey;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    $this->apiKey = file_get_contents(dirname(__FILE__) . '/../../apikey.txt');

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
  }

  public function testCallApi() {

    $class = new Tracdelight(new Client(), $this->entityManager, $this->apiKey);

    $products = $class->queryProducts(array('Query' => 'Hose'));

    $this->assertNotEmpty($products);

    foreach ($products as $product) {

      $this->assertTrue(!empty($product['ein']));
      $this->assertTrue(!empty($product['shop']));
      $this->assertTrue(!empty($product['formattedprice']));
      $this->assertTrue(!empty($product['price']));
      $this->arrayHasKey($product['oldprice']);
      $this->assertTrue(!empty($product['currency']));
      $this->assertTrue(strpos($product['detailpageurl'], 'http://td.oo34.net') !== FALSE);
      $this->assertTrue(strpos($product['imagebaseurl'], 'http://images.itemsearch.edelight.biz/') !== FALSE);

    }
  }

}
