<?php

namespace Drupal\Tests\nagios\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\nagios\Controller\StatuspageController;
use Drupal\nagios\EventSubscriber\MaintenanceModeSubscriber;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Tests the functionality to monitor cron.
 *
 * @group nagios
 */
class MaintenanceModeTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['nagios'];

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('nagios');
    StatuspageController::setNagiosStatusConstants();
  }

  public function testSubscriber() {
    $subscriber = new MaintenanceModeSubscriber();
    $get_response_event = $this->prophesize(GetResponseEvent::class);
    $request = $this->prophesize(Request::class);

    /** @noinspection PhpUndefinedMethodInspection */
    $request->getPathInfo()->willReturn('/nagios');

    /** @noinspection PhpUndefinedMethodInspection */
    $get_response_event->getRequest()
      ->willReturn($request->reveal())
      ->shouldBeCalled();

    $content = '';
    $set_response_content = function ($args) use (&$content) {
      /** @var \Symfony\Component\HttpFoundation\Response $response */
      $response = $args[0];
      $content = $response->getContent();
    };

    /** @noinspection PhpUndefinedMethodInspection */
    $get_response_event->setResponse(Argument::any())
      ->will($set_response_content)
      ->shouldBeCalled();

    $config = \Drupal::configFactory()->getEditable('nagios.settings');
    $config->set('nagios.statuspage.enabled', TRUE);
    $config->save();

    $_SERVER['HTTP_USER_AGENT'] = $config->get('nagios.ua');

    /** @noinspection PhpParamsInspection */
    $subscriber->onKernelRequestMaintenance($get_response_event->reveal());

    self::assertSame("\nnagios=OK,  | \n", $content);
  }
}
