<?php

namespace Drupal\Tests\agreement\Unit\EventSubscriber;

use Drupal\agreement\EventSubscriber\AgreementSubscriber;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the agreement route subscriber.
 *
 * @group agreement
 */
class AgreementSubscriberTest extends UnitTestCase {

  /**
   * Asserts that check for redirection method is functional.
   *
   * @param bool $canBypass
   *   Permission to bypass agreement.
   * @param bool $hasAgreement
   *   Whether or not an agreement is "found" in this test.
   * @param bool $expected
   *   Whether a redirect is expected or not.
   *
   * @dataProvider checkForRedirectionProvider
   */
  public function testCheckForRedirection($canBypass, $hasAgreement, $expected) {

    $pathProphet = $this->prophesize('\Drupal\Core\Path\CurrentPathStack');
    $pathProphet->getPath(Argument::any())->willReturn('test');

    $sessionProphet = $this->prophesize('\Drupal\Core\Session\SessionManagerInterface');

    $kernelProphet = $this->prophesize('\Drupal\Core\DrupalKernelInterface');

    $request = new Request();
    $event = new GetResponseEvent(
      $kernelProphet->reveal(),
      $request,
      HttpKernelInterface::MASTER_REQUEST
    );

    $subscriber = new AgreementSubscriber(
      $this->getAgreementHandlerStub($hasAgreement),
      $pathProphet->reveal(),
      $sessionProphet->reveal(),
      $this->getAccountStub($canBypass)
    );

    $subscriber->checkForRedirection($event);
    $isRedirect = $event->getResponse() !== NULL ? $event->getResponse()->isRedirect() : FALSE;
    $this->assertEquals($expected, $isRedirect);
  }

  /**
   * Get the mocked current user account object.
   *
   * @param bool $canBypass
   *   Can the user bypass agreement.
   *
   * @return object
   *   The mocked user account object.
   */
  protected function getAccountStub($canBypass = FALSE) {
    $accountProphet = $this->prophesize('\Drupal\Core\Session\AccountProxyInterface');
    $accountProphet->hasPermission('bypass agreement')->willReturn($canBypass);
    return $accountProphet->reveal();
  }

  /**
   * Get the mocked agreement handler.
   *
   * @param bool $willHaveAgreement
   *   Whether an agreement object should be returned or not.
   *
   * @return object
   *   The mocked agreement handler object.
   */
  protected function getAgreementHandlerStub($willHaveAgreement = FALSE) {
    $agreement = FALSE;
    if ($willHaveAgreement) {
      $agreementProphet = $this->prophesize('\Drupal\agreement\Entity\Agreement');
      $agreementProphet->get('path')->willReturn('test');
      $agreement = $agreementProphet->reveal();
    }

    $handlerProphet = $this->prophesize('\Drupal\agreement\AgreementHandlerInterface');
    $handlerProphet
      ->getAgreementByUserAndPath(Argument::any(), Argument::any())
      ->willReturn($agreement);
    return $handlerProphet->reveal();
  }

  /**
   * Provides test arguments and expectations.
   *
   * @return array
   *   An array of test arguments.
   */
  public function checkForRedirectionProvider() {
    return [
      // Bypass, Have agreement, Expected Response.
      [TRUE, FALSE, FALSE],
      [TRUE, TRUE, FALSE],
      [FALSE, FALSE, FALSE],
      [FALSE, TRUE, TRUE],
    ];
  }

}
