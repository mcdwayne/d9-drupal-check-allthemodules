<?php

namespace Drupal\Tests\entity_access_audit\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\entity_access_audit\AccessAuditResult;
use Drupal\entity_access_audit\AccessAuditResultCollection;
use Drupal\entity_access_audit\Dimensions\EntityOwnerDimension;
use Drupal\entity_access_audit\Dimensions\OperationDimension;
use Drupal\Tests\UnitTestCase;

/**
 * @group entity_access_audit
 * @coversDefaultClass \Drupal\entity_access_audit\AccessAuditResultCollection
 */
class AccessAuditResultCollectionTest extends UnitTestCase {

  /**
   * Get some test dimensions.
   *
   * @return \Drupal\entity_access_audit\AccessDimensionInterface[]
   *   Test access dimensions.
   */
  public function getTestDimensions() {
    return [
      EntityOwnerDimension::class => [
        new EntityOwnerDimension(TRUE),
        new EntityOwnerDimension(FALSE),
      ],
      OperationDimension::class => [
        new OperationDimension('foo'),
        new OperationDimension('bar'),
      ],
    ];
  }

  /**
   * @covers ::count
   */
  public function testCount() {
    $collection = new AccessAuditResultCollection($this->getTestDimensions());
    $this->assertEquals(0, $collection->count());
    $collection->addAuditResult(new AccessAuditResult(AccessResult::allowed(), []));
    $this->assertEquals(1, $collection->count());
  }

  /**
   * @covers ::getDimensionClasses
   * @covers ::getDimensionsOfType
   */
  public function testGetDimensionClasses() {
    $collection = new AccessAuditResultCollection($this->getTestDimensions());
    $this->assertEquals([EntityOwnerDimension::class, OperationDimension::class], $collection->getDimensionClasses());
    $this->assertEquals($this->getTestDimensions()[EntityOwnerDimension::class], $collection->getDimensionsOfType(EntityOwnerDimension::class));
  }

  /**
   * @covers ::getAuditResultMatchingDimensions
   */
  public function testGetAuditResultMatchingDimensions() {
    $collection = new AccessAuditResultCollection($this->getTestDimensions());

    $audit_result = new AccessAuditResult(AccessResult::allowed(), [new EntityOwnerDimension(TRUE), new OperationDimension('foo')]);
    $collection->addAuditResult($audit_result);

    $this->assertEquals($audit_result, $collection->getAuditResultMatchingDimensions([new EntityOwnerDimension(TRUE), new OperationDimension('foo')]));
  }

  /**
   * @covers ::getAuditResultMatchingDimensions
   */
  public function testGetAuditResultMatchingDimensionsException() {
    $collection = new AccessAuditResultCollection($this->getTestDimensions());

    $this->setExpectedException(\Exception::class, 'Could not audit result matching key: 834b3-1:c6e2a-foo');
    $collection->getAuditResultMatchingDimensions([new EntityOwnerDimension(TRUE), new OperationDimension('foo')]);
  }

}
