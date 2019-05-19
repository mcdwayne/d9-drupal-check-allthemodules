<?php

namespace Drupal\vsauce_sticky_popup\EventSubscriber;

/**
 * @file
 * Contains \Drupal\vsauce_sticky_popup\EventSubscriber\VsauceStickyPopupSubscriber.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class VsauceStickyPopupSubscriber.
 *
 * @package Drupal\vsauce_sticky_popup\EventSubscriber
 */
class VsauceStickyPopupSubscriber extends ControllerBase implements EventSubscriberInterface, ContainerInjectionInterface {

  /**
   * The messenger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current path id.
   *
   * @var null|string
   */
  protected $pathId;

  /**
   * The Route Match.
   *
   * @var \Drupal\Core\Routing\ResettableStackedRouteMatchInterface
   */
  protected $routeMatch;
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(MessengerInterface $messenger, ResettableStackedRouteMatchInterface $routeMatch, ConfigFactoryInterface $configFactory) {
    $this->messenger = $messenger;
    $this->pathId = $routeMatch->getRouteName();
    $this->configFactory = $configFactory;
  }

  /**
   * The Resposnse Event.
   *
   * @param \Symfony\Component\HttpFoundation\Response\GetResponseEvent $event
   *   The GetResponseEvent.
   */
  public function showPathId(GetResponseEvent $event) {

    // Check options dev mode for Vsauce sticky popup.
    if ($this->checkDevModeVsauceStickyPopup()) {

      // Set message.
      $message = $this->t('Vsauce Sticky Popup -> Development Mode: Current path_id is: %path_id', ['%path_id' => $this->pathId]);

      // Show message.
      $this->messenger->addMessage($message);
    }
  }

  /**
   * Check Dev Mode Vsauce Sticky Popup.
   *
   * @return bool
   *   true || false.
   */
  private function checkDevModeVsauceStickyPopup() {
    return $this->configFactory->get('vsauce_sticky_popup.default_config')->get('show_path_id');
  }

  /**
   * SubscribeEvents.
   *
   * @return array|mixed
   *   SubscribeEvents.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['showPathId'];
    return $events;
  }

}
