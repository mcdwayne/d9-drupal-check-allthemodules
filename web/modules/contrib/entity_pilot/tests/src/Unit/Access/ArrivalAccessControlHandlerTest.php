<?php

namespace Drupal\Tests\entity_pilot\Unit\Access;

use Drupal\entity_pilot\Access\ArrivalAccessControlHandler;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\entity_pilot\Access\ArrivalAccessControlHandler
 * @group entity_pilot
 */
class ArrivalAccessControlHandlerTest extends UnitTestCase {

  use CacheContextContainerBuilderTrait;
  /**
   * Controller under test.
   *
   * @var \Drupal\entity_pilot\Access\ArrivalAccessControlHandler
   */
  protected $AccessControlHandler;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->setupCacheContextsManagerAndContainer();
    $entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->AccessControlHandler = new ArrivalAccessControlHandler($entity_type);
  }

  /**
   * Tests \Drupal\entity_pilot\ArrivalAccessControlHandler::checkAccess().
   *
   * @see \Drupal\entity_pilot\ArrivalAccessControlHandler::checkAccess()
   */
  public function testCheckAccess() {
    $method = new \ReflectionMethod($this->AccessControlHandler, 'checkAccess');
    $method->setAccessible(TRUE);
    $entity = $this->getMockBuilder('\Drupal\entity_pilot\Entity\Arrival')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->once())
      ->method('isPending')
      ->will($this->returnValue(FALSE));
    $entity->expects($this->once())
      ->method('isReady')
      ->will($this->returnValue(FALSE));
    $entity->expects($this->once())
      ->method('isQueued')
      ->will($this->returnValue(TRUE));
    $entity->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['ep_arrival' => 1]);
    $account = $this->getMockBuilder('\Drupal\Core\Session\AccountProxy')
      ->disableOriginalConstructor()
      ->getMock();
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturn(TRUE);
    $this->assertFalse($method->invoke($this->AccessControlHandler, $entity, 'approve', $account)->isAllowed());
    $this->assertFalse($method->invoke($this->AccessControlHandler, $entity, 'queue', $account)->isAllowed());
    $this->assertFalse($method->invoke($this->AccessControlHandler, $entity, 'update', $account)->isAllowed());
  }

}
