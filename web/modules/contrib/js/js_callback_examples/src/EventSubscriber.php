<?php

namespace Drupal\js_callback_examples;

use Drupal\Core\Url;
use Drupal\js\Js;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * EventSubscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\js\Js
   */
  protected $js;

  /**
   * EventSubscriber constructor.
   *
   * @param \Drupal\js\Js $js
   *   The JS Callback service.
   */
  public function __construct(Js $js) {
    $this->js = $js;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onRequest']];
  }

  /**
   * For
   */
  public function onRequest() {
    // Only show this message if not executing a JS Callback.
    if (!$this->js->isExecuting()) {
      drupal_set_message(t('You have the JS Example module enabled. Please make sure you disable it when you are done. View <a href=":url">example page</a>.', [
        ':url' => Url::fromRoute('js_callback_examples.form')->toString(),
      ]), 'status');
    }
  }

}
