<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\Core\Cache\NullBackend;
use Drupal\xero\XeroQuery;

/**
 * @group Xero
 */
class XeroQueryClientTest extends XeroQueryTestBase {

  /**
   * Provider for client FALSE or with mocked client.
   *
   * @return array
   *   A set of arrays that contain the first argument for XeroQuery, and the
   *   expected result of XeroQuery::hasClient().
   */
  public function clientProvider() {
    return [
      [FALSE, FALSE],
      [$this->client, TRUE]
    ];
  }

  /**
   * Test XeroQuery::hasClient().
   *
   * @dataProvider clientProvider
   */
  public function testHasClient($client, $result) {
     $cache = new NullBackend('xero_query');
     $query = new XeroQuery($client, $this->serializer, $this->typedDataManager, $this->loggerFactory, $cache);
     $this->assertEquals($result, $query->hasClient());
  }
}
