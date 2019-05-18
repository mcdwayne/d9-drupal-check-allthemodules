<?php

namespace Drupal\commerce_ajax_cart_message\EventSubscriber;

use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\EventSubscriber\CartEventSubscriber;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Commerce ajax cart message event subscriber.
 */
class CommerceAjaxCartMessageSubscriber extends CartEventSubscriber {

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $currentRequest;

  /**
   * Constructs a new CommerceAjaxCartMessageSubscriber object.
   */
  public function __construct(MessengerInterface $messenger, TranslationInterface $string_translation, RequestStack $request_stack) {
    parent::__construct($messenger, $string_translation);
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function displayAddToCartMessage(CartEntityAddEvent $event) {
    $is_ajax = $this->currentRequest->isXmlHttpRequest();
    if (!$is_ajax) {
      parent::displayAddToCartMessage($event);
    }
  }

}
