<?php

namespace Drupal\snippet_manager_test\EventSubscriber;

use Drupal\Core\Display\VariantManager;
use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Display variant subscriber.
 */
class DisplayVariantSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The variant manager.
   *
   * @var \Drupal\Core\Display\VariantManager
   */
  protected $variantManager;

  /**
   * Constructs subscriber object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Display\VariantManager $variant_manager
   *   The variant manager.
   */
  public function __construct(RequestStack $request_stack, VariantManager $variant_manager) {
    $this->requestStack = $request_stack;
    $this->variantManager = $variant_manager;
  }

  /**
   * Event callback.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onSelectPageDisplayVariant(PageDisplayVariantSelectionEvent $event) {
    $display_variant = $this->requestStack->getCurrentRequest()->get('display-variant');
    if ($display_variant) {
      $event->setPluginId($display_variant);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onSelectPageDisplayVariant'];
    return $events;
  }

}
