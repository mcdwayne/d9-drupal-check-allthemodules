<?php

/**
 * @file
 * Contains \Drupal\testmodule\DefaultSubscriber.
 */

namespace Drupal\testmodule\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\EventSubscriber\ResponseGeneratorSubscriber;
use Drupal\Core\Template\TwigEnvironment;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DefaultSubscriber.
 *
 * @package Drupal\testmodule
 */
class DefaultSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\EventSubscriber\ResponseGeneratorSubscriber definition.
   *
   * @var Drupal\Core\EventSubscriber\ResponseGeneratorSubscriber
   */
  protected $response_generator_subscriber;

  /**
   * Drupal\Core\Template\TwigEnvironment definition.
   *
   * @var Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * Symfony\Component\HttpFoundation\RequestStack definition.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request_stack;

  /**
   * Constructor.
   */
  public function __construct(ResponseGeneratorSubscriber $response_generator_subscriber, TwigEnvironment $twig, RequestStack $request_stack) {
    $this->response_generator_subscriber = $response_generator_subscriber;
    $this->twig = $twig;
    $this->request_stack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.response'] = ['HideResponse'];
    $events['kernel.request'] = ['HideRequest'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.response event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function HideResponse(Event $event) {
    drupal_set_message('Event kernel.response thrown by Subscriber in module testmodule.', 'status', TRUE);
  }
  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function HideRequest(Event $event) {
    drupal_set_message('Event kernel.request thrown by Subscriber in module testmodule.', 'status', TRUE);
  }

}
