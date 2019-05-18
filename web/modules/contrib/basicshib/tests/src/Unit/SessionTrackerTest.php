<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/15/17
 * Time: 12:05 PM
 */

namespace Drupal\Tests\Unit\basicshib;


use Drupal\basicshib\SessionTracker;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionTrackerTest extends UnitTestCase {

  public function testGet() {
    $value = $this->randomMachineName();

    /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
    $session = $this->getMockForAbstractClass(SessionInterface::class);
    $session->method('get')
      ->willReturn($value);

    $tracker = new SessionTracker($session);
    $this->assertEquals($value, $tracker->get());
  }

  public function testSetNotNull() {
    $value = $this->randomMachineName();

    /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
    $session = $this->getMockForAbstractClass(SessionInterface::class);
    $session->method('set')
      ->with($this->equalTo(SessionTracker::VARNAME), $this->equalTo($value))
      ->willThrowException(new \Exception($value));

    $tracker = new SessionTracker($session);
    try {
      $tracker->set($value);
    }
    catch (\Exception $exception) {
      $this->assertEquals($value, $exception->getMessage());
    }

    $this->assertNotFalse(isset($exception));
  }

  public function testClear() {
    $value = $this->randomMachineName();

    /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
    $session = $this->getMockForAbstractClass(SessionInterface::class);
    $session->method('remove')
      ->with($this->equalTo(SessionTracker::VARNAME))
      ->willThrowException(new \Exception($value));

    $tracker = new SessionTracker($session);
    try {
      $tracker->clear();
    }
    catch (\Exception $exception) {
      $this->assertEquals($value, $exception->getMessage());
    }

    $this->assertNotFalse(isset($exception));

  }
}
