<?php

namespace Drupal\contacts\EventSubscriber;

use Drupal\contacts\Event\UserCancelConfirmationEvent;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber invoked on user cancel confirmation form.
 *
 * Provides additional information, confirmations and errors about the
 * cancellation based on Commerce orders.
 */
class OrderUserCancelConfirmationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The database connection for the Drupal Commerce tables.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * OrderUserCancelConfirmationSubscriber constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Invoked when a user cancel form is created and commerce_order is enabled.
   *
   * @param \Drupal\contacts\Event\UserCancelConfirmationEvent $event
   *   The event representing the user cancel confirmation.
   */
  public function onCancelConfirm(UserCancelConfirmationEvent $event) {
    // Only react to the event if commerce_order is enabled.
    if (!$this->moduleHandler->moduleExists('commerce_order')) {
      return;
    }

    $user = $event->getUser();

    // Check for any commerce orders for the event user.
    $query = $this->database->select('commerce_order', 'co');
    $query->fields('co', ['order_id']);
    $query->condition('uid', $user->id());
    $user_orders = $query->execute()->fetchAll();

    if (count($user_orders)) {
      $error = $this->formatPlural(count($user_orders), '%user has an order', '%user has %count orders', [
        '%count' => count($user_orders),
        '%user' => $user->label(),
      ]);
      $event->addError($error);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[UserCancelConfirmationEvent::NAME][] = ['onCancelConfirm'];
    return $events;
  }

}
