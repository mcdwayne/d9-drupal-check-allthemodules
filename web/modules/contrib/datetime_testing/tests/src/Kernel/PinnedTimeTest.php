<?php

namespace Drupal\Tests\datetime_testing\Kernel;

use Drupal\Component\Datetime\Time;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\datetime_testing\TestTime;

/**
 * Tests datetime_testing's time by overriding php's native time functions.
 *
 * Test that class behaves in the same way as the regular time class, if not
 * explicitly instructed to behave otherwise, and test its methods for
 * manipulating the time. A class at the bottom of this file overrides php's
 * built-in time methods and pins the current time.
 *
 * @coversDefaultClass \Drupal\datetime_testing\TestTime
 * @group datetime_testing
 *
 * Isolate the tests to prevent side effects from altering system time.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PinnedTimeTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'datetime_testing',
  ];

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The (mocked) normal time class.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $normalTime;

  /**
   * Our testing time class.
   *
   * @var \Drupal\datetime_testing\TestTimeInterface
   */
  protected $testTime;

  protected $mockTime = 1000000;
  protected $mockMicroTime = 1000000.10;
  protected $mockRequestTime = 1000000;
  protected $mockRequestMicroTime = 1000000.20;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->getMock();
    $this->normalTime = new Time($this->requestStack);
    $this->testTime = new TestTime($this->normalTime, \Drupal::state());

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME', $this->mockRequestTime);
    $request->server->set('REQUEST_TIME_FLOAT', $this->mockRequestMicroTime);

    // Mocks a the request stack getting the current request.
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->testTime->resetTime();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->testTime->resetTime();
    parent::tearDown();
  }

  /**
   * Make sure the time service is being decorated with TestTime.
   */
  public function testDecoration() {
    $service = \Drupal::time();
    $this->assertEquals('Drupal\\datetime_testing\\TestTime', get_class($service));
  }

  /**
   * Tests the getRequestTime method.
   *
   * @covers ::getRequestTime
   */
  public function testGetRequestTime() {
    $this->assertEquals($this->mockRequestTime, $this->normalTime->getRequestTime());
    $this->assertEquals($this->mockRequestTime, $this->testTime->getRequestTime());
  }

  /**
   * Tests the getRequestMicroTime method.
   *
   * @covers ::getRequestMicroTime
   */
  public function testGetRequestMicroTime() {
    $this->assertEquals($this->mockRequestMicroTime, $this->normalTime->getRequestMicroTime(), '', 0.0001);
    $this->assertEquals($this->mockRequestMicroTime, $this->testTime->getRequestMicroTime(), '', 0.0001);
  }

  /**
   * Tests the getCurrentTime method.
   *
   * @covers ::getCurrentTime
   */
  public function testGetCurrentTime() {
    $this->assertEquals($this->mockTime, $this->normalTime->getCurrentTime());
    $this->assertEquals($this->mockTime, $this->testTime->getCurrentTime());
  }

  /**
   * Tests the getCurrentMicroTime method.
   *
   * @covers ::getCurrentMicroTime
   */
  public function testGetCurrentMicroTime() {
    $this->assertEquals($this->mockMicroTime, $this->normalTime->getCurrentMicroTime(), '', 0.0001);
    $this->assertEquals($this->mockMicroTime, $this->testTime->getCurrentMicroTime(), '', 0.0001);
  }

  /**
   * Tests the setTime method with whole number of seconds.
   */
  public function testSetTimeInteger() {
    $seconds = 2000000;
    $this->testTime->setTime($seconds);
    $this->assertEquals($seconds, $this->testTime->getCurrentMicroTime(), '', 0.0001);
    $this->assertEquals($seconds, $this->testTime->getCurrentTime());
    $this->assertEquals($seconds + 0.1, $this->testTime->getRequestMicroTime(), '', 0.0001);
    $this->assertEquals($seconds, $this->testTime->getRequestTime());
  }

  /**
   * Tests the setTime method with fractions of a second.
   */
  public function testSetTimeFloat() {
    $seconds = 2000000;
    $milliseconds = 0.85;
    $this->testTime->setTime($seconds + $milliseconds);
    $this->assertEquals($seconds + $milliseconds, $this->testTime->getCurrentMicroTime(), '', 0.0001);
    // $milliseconds < 1, so do not increment expected time.
    $this->assertEquals($seconds, $this->testTime->getCurrentTime());
    $this->assertEquals($seconds + $milliseconds + 0.1, $this->testTime->getRequestMicroTime(), '', 0.0001);
    // $milliseconds + 0.1 request time gap < 1, so don't increment the expected
    // time.
    $this->assertEquals($seconds, $this->testTime->getRequestTime());
  }

  /**
   * Tests the setTime method with a string.
   */
  public function testSetTimeString() {
    $seconds = 2000000;
    $this->testTime->setTime("1970-01-24 03:33:20 UTC");
    $this->assertEquals($seconds, $this->testTime->getCurrentMicroTime(), '', 0.0001);
    $this->assertEquals($seconds, $this->testTime->getCurrentTime());
    $this->assertEquals($seconds + 0.1, $this->testTime->getRequestMicroTime(), '', 0.0001);
    $this->assertEquals($seconds, $this->testTime->getRequestTime());
  }

  /**
   * Tests the shiftTime method with whole number of seconds.
   */
  public function testSetTimeRelative() {
    $seconds = 20;
    $this->testTime->setTime("$seconds seconds");
    $this->assertEquals($this->mockTime + $seconds, $this->testTime->getCurrentTime());
    $this->assertEquals($this->mockTime + $seconds, (int) $this->testTime->getCurrentMicroTime());
    $this->assertEquals($this->mockRequestTime + $seconds, $this->testTime->getRequestTime());
    $this->assertEquals($this->mockRequestTime + $seconds, (int) $this->testTime->getRequestMicroTime());
  }

  /**
   * Tests the resetTime method.
   */
  public function testResetTime() {
    $this->testTime->setTime(0.5);
    $this->testTime->resetTime();
    $this->assertEquals($this->mockMicroTime, $this->testTime->getCurrentMicroTime(), '', 0.0001);
    $this->assertEquals($this->mockTime, $this->testTime->getCurrentTime());
    $this->assertEquals($this->mockRequestMicroTime, $this->testTime->getRequestMicroTime(), '', 0.0001);
    $this->assertEquals($this->mockRequestTime, $this->testTime->getRequestTime());
  }

}

namespace Drupal\Component\Datetime;

/**
 * Shadow time() system call.
 *
 * @returns int
 */
function time() {
  return 1000000;
}

/**
 * Shadow microtime system call.
 *
 * @returns float
 */
function microtime() {
  return 1000000.10;
}
