<?php

namespace Drupal\Tests\opigno_wt_app\Unit;

use Drupal\opigno_wt_app\Model\HelloWorld;
use Drupal\Tests\UnitTestCase;

/**
 * Class HelloControllerTest
 *
 * @group Opigno WT App
 */
class HelloControllerTest extends UnitTestCase {
  public function testHelloWorld() {
    $hello = HelloWorld::getHelloWorld();
    $this->assertEquals('Hello World !', $hello);
  }
}