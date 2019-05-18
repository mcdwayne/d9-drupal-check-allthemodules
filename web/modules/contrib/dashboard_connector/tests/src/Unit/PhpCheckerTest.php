<?php

namespace Drupal\Tests\dashboard_connector\Unit;

use Drupal\dashboard_connector\Checker\PhpChecker;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the performance checker plugin.
 *
 * @group dashboard_connector
 */
class PhpCheckerTest extends UnitTestCase {

  /**
   * Tests the php version check.
   */
  public function testPhpVersion() {
    $translation = $this->prophesize('Drupal\Core\StringTranslation\TranslationInterface')->reveal();
    $checks = [];

    // Pass PHP 5.5.
    $checker = new PhpChecker($translation, $this->getRequestStack(1449705058), 50500);
    $checks = array_merge($checks, $checker->getChecks());

    // Fail PHP 5.4 because it's old.
    $checker = new PhpChecker($translation, $this->getRequestStack(1449705058), 50400);
    $checks = array_merge($checks, $checker->getChecks());

    // Fail PHP 5.6 because it's the future.
    $checker = new PhpChecker($translation, $this->getRequestStack(1504978442), 50600);
    $checks = array_merge($checks, $checker->getChecks());

    $this->assertNotEmpty($checks);

    $pass_check = $checks[0];
    $this->assertEquals($pass_check['name'], 'version');
    $this->assertEquals($pass_check['type'], 'php');
    $this->assertEquals($pass_check['alert_level'], 'notice');

    $fail_version_check = $checks[1];
    $this->assertEquals($fail_version_check['name'], 'version');
    $this->assertEquals($fail_version_check['type'], 'php');
    $this->assertEquals($fail_version_check['alert_level'], 'error');

    $fail_time_check = $checks[2];
    $this->assertEquals($fail_time_check['name'], 'version');
    $this->assertEquals($fail_time_check['type'], 'php');
    $this->assertEquals($fail_time_check['alert_level'], 'error');
  }

  /**
   * Gets a request stack that contains a request with the specified time.
   *
   * @param int $request_time
   *   The request time of the request.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack.
   */
  protected function getRequestStack($request_time) {
    $request_stack = new RequestStack();
    $request = $this->prophesize('Symfony\Component\HttpFoundation\Request');
    $request->get('REQUEST_TIME')->willReturn($request_time);
    $request_stack->push($request->reveal());
    return $request_stack;
  }

}
