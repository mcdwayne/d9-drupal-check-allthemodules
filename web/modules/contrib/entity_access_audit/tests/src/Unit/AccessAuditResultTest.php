<?php

namespace Drupal\Tests\entity_access_audit;

use Drupal\Core\Access\AccessResult;
use Drupal\entity_access_audit\AccessAuditResult;
use Drupal\entity_access_audit\Dimensions\EntityOwnerDimension;
use Drupal\entity_access_audit\Dimensions\OperationDimension;
use Drupal\Tests\UnitTestCase;

/**
 * @group entity_access_audit
 * @coversDefaultClass \Drupal\entity_access_audit\AccessAuditResult
 */
class AccessAuditResultTest extends UnitTestCase {

  /**
   * Test the access audit result value object.
   */
  public function testAccessAuditResult() {
    $dimensions = [
      OperationDimension::class => new OperationDimension('foo'),
      EntityOwnerDimension::class => new EntityOwnerDimension('bar'),
    ];
    $access_result = AccessResult::allowed();
    $audit_result = new AccessAuditResult($access_result, $dimensions);

    $this->assertEquals($dimensions, $audit_result->getDimensions());
    $this->assertTrue($audit_result->hasDimension(OperationDimension::class));
    $this->assertEquals($access_result, $audit_result->getAccessResult());
  }

}
