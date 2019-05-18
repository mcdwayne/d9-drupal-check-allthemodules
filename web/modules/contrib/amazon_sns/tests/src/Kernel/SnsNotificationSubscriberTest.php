<?php

namespace Drupal\Tests\amazon_sns\Kernel;

use Drupal\amazon_sns\Controller\NotificationController;
use Drupal\amazon_sns\Event\SnsEvents;
use Drupal\amazon_sns\Event\SnsNotificationSubscriber;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\amazon_sns\Unit\PlainTextMessageTrait;
use Symfony\Component\Debug\BufferingLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the notification controller and error handling.
 *
 * @group amazon_sns
 */
class SnsNotificationSubscriberTest extends KernelTestBase {
  use PlainTextMessageTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'amazon_sns',
  ];

  /**
   * Test logging notifications when enabled.
   */
  public function testLogNotification() {
    $config = $this->config('amazon_sns.settings');
    $config->set('log_notifications', TRUE);
    $config->save();

    $logger = new BufferingLogger();
    $this->container->get('logger.factory')->addLogger($logger);
    $controller = NotificationController::create($this->container);
    $request = Request::create('http://example.com/_amazon-sns/notify', 'POST', [], [], [], $this->getFixtureServer(), $this->getFixtureBody());
    $controller->receive($request);
    $message = $logger->cleanLogs()[0];
    $this->assertEquals(RfcLogLevel::INFO, $message[0]);
    $this->assertEquals("Notification %message-id received for topic %topic.", $message[1]);
    $context = [
      '%message-id' => 'f93edf3e-bee9-57f3-8752-8e97b283e829',
      '%topic' => 'arn:aws:sns:us-east-1:222524823419:drupal-sns-test',
      'channel' => 'amazon_sns',
    ];
    $this->assertArraySubset($context, $message[2]);
  }

  /**
   * Test that nothing is logged when it's disabled.
   */
  public function testLogDisabled() {
    $config = $this->config('amazon_sns.settings');
    $config->set('log_notifications', FALSE);
    $config->save();

    $logger = new BufferingLogger();
    $this->container->get('logger.factory')->addLogger($logger);
    $controller = NotificationController::create($this->container);
    $request = Request::create('http://example.com/_amazon-sns/notify', 'POST', [], [], [], $this->getFixtureServer(), $this->getFixtureBody());
    $controller->receive($request);
    $this->assertEmpty($logger->cleanLogs());
  }

  /**
   * Test that our logger is first in the service container.
   *
   * Note that if a site defines another subscriber with 100 priority, ours may
   * still not be first.
   */
  public function testLoggerIsFirst() {
    $dispatcher = $this->container->get('event_dispatcher');

    // Add a dummy listener at the default priority.
    $dispatcher->addListener(SnsEvents::NOTIFICATION, function () {});

    $listeners = $dispatcher->getListeners(SnsEvents::NOTIFICATION);
    $this->assertInstanceOf(SnsNotificationSubscriber::class, $listeners[0][0]);
  }

}
