<?php

/**
 * @file
 * Contains \Drupal\hookalyzer\EventSubscriber\HookDataDumper.
 */

namespace Drupal\hookalyzer\EventSubscriber;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hookalyzer\ModuleHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Dumps data from hook inspections onto the page for viewing.
 */
class HookDataDumper implements EventSubscriberInterface {

  protected $moduleHandler;
  protected $negotiation;

  public function __construct(ModuleHandler $handler, ContentNegotiation $negotiation) {
    $this->moduleHandler = $handler;
    $this->negotiation = $negotiation;
  }

  public function onController() {
    // TODO this is HILARIOUSLY wrong
    drupal_add_css(drupal_get_path('module', 'hookalyzer') . '/css/hookalyzer.css');
  }

  /**
   * Inject the hook data onto the page.
   */
  public function onResponse(FilterResponseEvent $event) {
    if (!$this->moduleHandler instanceof ModuleHandler) {
      return;
    }

    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST ||
        $this->negotiation->getContentType($event->getRequest()) !== 'html') {
      return;
    }

    $logs = $this->moduleHandler->getAlterLog();


    $outputs = array();
    foreach ($logs as $cid => $collections) {
      $outputs[$cid] = theme('hookalyzer_html_table', array('cid' => $cid, 'collections' => $collections));
    }
    $content = $event->getResponse()->getContent();
    $pos = strripos($content, '</body>');
    $event->getResponse()->setContent(substr_replace($content, implode('', $outputs) . substr($content, $pos), $pos));
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponse');
    // TODO this is totally the wrong time to add CSS, LULZ
    $events[KernelEvents::CONTROLLER][] = array('onController');

    return $events;
  }
}