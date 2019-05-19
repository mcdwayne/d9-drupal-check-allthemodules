<?php
/**
 * @file
 * Contains \Drupal\xhprof_sample\EventSubscriber\XHProfSampleEventSubscriber.
 */

namespace Drupal\xhprof_sample\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\xhprof_sample\XHProfSample\CollectorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class XHProfSampleEventSubscriber
 */
class XHProfSampleEventSubscriber implements EventSubscriberInterface {
  /**
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration service.
   * @param Drupal\xhprof_sample\XHProfSample\CollectorInterface $collector
   *   Active Collector service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, CollectorInterface $collector) {
    $this->configFactory = $configFactory;
    $this->collector = $collector;
  }

  /**
   * Fires at the beginning of a request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Kernel event object.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    if ($this->collector->canEnable($event->getRequest())) {
      $this->collector->enable();
    }
  }

  /**
   * Fires after the response has been sent to the client.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   Kernel event object.
   */
  public function onKernelTerminate(PostResponseEvent $event) {
    if ($this->collector->isEnabled()) {
      $this->collector->shutdown();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      KernelEvents::REQUEST => array('onKernelRequest', 0),
      KernelEvents::TERMINATE => array('onKernelTerminate', 0),
    );
  }
}
