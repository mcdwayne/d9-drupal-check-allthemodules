<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 2/6/16
 * Time: 3:59 PM
 */

namespace Drupal\Tests\forena\Unit;

/**
 * Test forena api.
 * @group Forena
 * @covers Drupal\forna\Frx
 * @requires module forena
 */
class APITest extends FrxTestCase {

  public function testDataContext() {
    $site = $this->getDataContext('site');
    $this->assertArrayHasKey('base_url', $site);
  }
}