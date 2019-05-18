<?php

/**
 * @file
 * Contains \Drupal\hooks_test_hooks\TestEventSubscriber
 */

namespace Drupal\hooks_test_hooks;

use Drupal\hooks\Event\HookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test subscriber for testing the hooks as events.
 */
class TestEventSubscriber implements EventSubscriberInterface {

  /**
   * Test hook works for single hook firing.
   */
  public function onTestHook(HookEvent $event, $event_name, $dispatcher) {
    $data = $event->getData() . ' Manipulated by onTestHook';
    $event->setContext1('ontesthook_context1');
    $event->setContext2('ontesthook_context2');
    $event->setData(ltrim($data));
  }

  /**
   * Test hook works for multiple hook firing.
   */
  public function onTestHookMultiple(HookEvent $event, $event_name, $dispatcher) {
    $data = $event->getData() . ' Manipulated by onTestHookMultiple';
    $event->setData(ltrim($data));
  }

  /**
   * When we make no changes the values should not change.
   */
  public function onTestNoChange(HookEvent $event, $event_name, $dispatcher) {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['hooks.test_hook'][] = ['onTestHook'];
    $events['hooks.test_hook_multiple'][] = ['onTestHookMultiple'];
    $events['hooks.test_hook_no_changes'][] = ['onTestNoChange'];
    return $events;
  }

}
