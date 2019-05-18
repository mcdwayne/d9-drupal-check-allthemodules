<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 10.02.17
 * Time: 00:12
 */

namespace Drupal\Tests\elastic_search\Unit\ValueObject;

use Drupal\elastic_search\ValueObject\IdDetails;
use Drupal\Tests\UnitTestCase;

/**
 * Class IdDetailsTest
 *
 * @group elastic_search
 */
class IdDetailsTest extends UnitTestCase {

  /**
   * Test that what you put in is what you get out
   */
  public function testIdDetails() {
    $entity = 'entity';
    $bundle = 'bundle';
    $id = new IdDetails($entity, $bundle);

    $this->assertEquals($entity, $id->getEntity());
    $this->assertEquals($bundle, $id->getBundle());
    $this->assertEquals($entity . '__' . $bundle, $id->getId());
  }

  /**
   * Test ID details when the bundle valis is empty
   */
  public function testIdDetailsEmptyBundle() {
    $entity = 'entity';
    $bundle = '';
    $id = new IdDetails($entity, $bundle);

    $this->assertEquals($entity, $id->getEntity());
    $this->assertEquals($bundle, $id->getBundle());
    $this->assertEquals($entity, $id->getId());
  }

  /**
   * @expectedException \twhiston\twLib\Immutable\ImmutableException
   */
  public function testAssertIsImmutableVar() {
    $entity = 'entity';
    $bundle = 'bundle';
    $id = new IdDetails($entity, $bundle);
    $id->$entity = 'new';
  }

  /**
   * @expectedException \twhiston\twLib\Immutable\ImmutableException
   */
  public function testAssertIsImmutableSet() {
    $entity = 'entity';
    $bundle = 'bundle';
    $id = new IdDetails($entity, $bundle);
    $id->__set($entity, 'new');
  }

}
