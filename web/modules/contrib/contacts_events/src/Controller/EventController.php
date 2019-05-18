<?php

namespace Drupal\contacts_events\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\contacts_events\Entity\EventInterface;
use Drupal\contacts_events\Plugin\Commerce\CheckoutFlow\BookingFlow;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EventController.
 */
class EventController extends ControllerBase {

  /**
   * Booking settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $bookingSettings;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The events logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Constructs a new EventController object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, AccountProxy $current_user, RedirectDestination $redirect_destination, ConfigFactory $config_factory, Messenger $messenger, LoggerChannelInterface $logger_channel, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->redirectDestination = $redirect_destination;
    $this->configFactory = $config_factory;
    $this->bookingSettings = $this->config('contacts_events.booking_settings');
    $this->messenger = $messenger;
    $this->loggerChannel = $logger_channel;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('redirect.destination'),
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('logger.channel.contacts_events'),
      $container->get('module_handler')
    );
  }

  /**
   * Book onto an event.
   *
   * @param \Drupal\contacts_events\Entity\EventInterface $contacts_event
   *   The event to book for.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirection to the checkour process.
   */
  public function book(EventInterface $contacts_event) {
    $access_result = $contacts_event->access('book', $this->currentUser, TRUE);

    // Always deny if explicitly forbidden.
    if ($access_result->isForbidden()) {
      return $this->deniedRedirect($contacts_event, $this->t('Sorry, we were unable to start a booking for %event', [
        '%event' => $contacts_event->label(),
      ]));
    }

    // Otherwise, look for an existing order to redirect to.
    $booking = $this->findBooking($contacts_event, $this->currentUser);
    if ($booking) {
      return $this->bookingRedirect($booking);
    }

    // If we are allowed to book.
    if (!$this->currentUser->hasPermission('can book for contacts_events')) {
      // Give an anonymous user a chance to log in.
      if ($this->currentUser->isAnonymous()) {
        $url = Url::fromRoute('user.login', [], ['query' => ['destination' => $this->redirectDestination->get()]]);
        return new RedirectResponse($url->toString());
      }

      // Otherwise redirect to the event page.
      return $this->deniedRedirect($contacts_event);
    }

    // Ensure the system is configured.
    if (!$this->checkConfiguration()) {
      // If we have permission to update settings, give a directive message.
      $message = NULL;
      if ($this->currentUser->hasPermission('configure contacts events')) {
        $link = Link::createFromRoute($this->t('booking settings'), 'contacts_events.contacts_events_booking_settings_form');
        $message = $this->t('You must configure the @link before booking onto an event.', [
          '@link' => $link,
        ]);
      }
      return $this->deniedRedirect($contacts_event, $message);
    }

    // Finally allow other modules to deny with a reason.
    $args = [$contacts_event, $this->currentUser];
    foreach ($this->moduleHandler->getImplementations('contacts_events_deny_booking') as $module) {
      $denial_reason = $this->moduleHandler->invoke($module, 'contacts_events_deny_booking', $args);
      if ($denial_reason) {
        return $this->deniedRedirect($contacts_event, $denial_reason);
      }
    }

    // Build a new booking, redirecting direct into the booking process.
    $booking = $this->createBooking($contacts_event, $this->currentUser);
    return $this->bookingRedirect($booking, 'tickets');
  }

  /**
   * Find a booking for an event and user.
   *
   * @param \Drupal\contacts_events\Entity\EventInterface $event
   *   The event entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user. If not provided, we use the current user.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The order, if available.
   */
  protected function findBooking(EventInterface $event, AccountInterface $account = NULL) {
    // If we didn't get an account, use the current user.
    if (!$account) {
      $account = $this->currentUser;
    }

    // Query booking for this event for this user.
    $storage = $this->entityTypeManager->getStorage('commerce_order');
    $query = $storage->getQuery();
    $query->condition('uid', $account->id());
    $query->condition('type', 'contacts_booking');
    $query->condition('event', $event->id());
    $result = $query->execute();

    // If we have an ID, attempt to load the booking.
    if ($id = reset($result)) {
      return $storage->load($id);
    }

    return NULL;
  }

  /**
   * Create a booking for an event and user.
   *
   * @param \Drupal\contacts_events\Entity\EventInterface $event
   *   The event entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user. If not provided, we use the current user.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The created and saved booking, ready to continue.
   */
  protected function createBooking(EventInterface $event, AccountInterface $account = NULL) {
    // If we didn't get an account, use the current user.
    if (!$account) {
      $account = $this->currentUser;
    }

    // Intial values for the booking.
    $values = [
      'type' => 'contacts_booking',
      'store_id' => $this->bookingSettings->get('store_id'),
      'event' => $event->id(),
      'uid' => $account->id(),
      'checkout_step' => 'tickets',
    ];

    // Look for a customer profile we can use for billing records.
    $storage = $this->entityTypeManager
      ->getStorage('profile');
    $billing_query = $storage->getQuery();
    $billing_query->condition('uid', $account->id());
    $billing_query->condition('type', 'customer');
    $billing_query->condition('is_default', TRUE);
    $billing_ids = $billing_query->execute();
    if ($billing_id = reset($billing_ids)) {
      // @todo: Re-enable this when we are confident it's safe.
      // $values['billing_profile'] = $storage->load($billing_id);
    }

    // Create, save and return the booking.
    $booking = $this->entityTypeManager
      ->getStorage('commerce_order')
      ->create($values);
    $booking->save();
    return $booking;
  }

  /**
   * Check the configuration for bookings.
   *
   * @return bool
   *   Whether the configuration is valid.
   */
  protected function checkConfiguration() {
    // Ensure the system is configured.
    if (!$store_id = $this->bookingSettings->get('store_id')) {
      // Log a critical error if the store is not configured.
      $this->loggerChannel->critical('The system is not correctly configured for bookings.');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Redirect to the event page with a denied message.
   *
   * @param \Drupal\contacts_events\Entity\EventInterface $event
   *   The event entity.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string|null|false $message
   *   The message to show. NULL will use the default and FALSE will not use
   *   one.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  protected function deniedRedirect(EventInterface $event, $message = NULL) {
    // Set the default message.
    if (!isset($message)) {
      $message = $this->t('Sorry, we were unable to start a booking for %event', [
        '%event' => $event->label(),
      ]);
    }

    // Show the message if there is one.
    if ($message) {
      $this->messenger->addError($message);
    }

    // Redirect to the event page.
    return new RedirectResponse($event->toUrl()->toString());
  }

  /**
   * Redirect to the booking page.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $booking
   *   The booking to redirect to.
   * @param string $step
   *   The step on the booking process. Defaults to the summary.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  protected function bookingRedirect(OrderInterface $booking, $step = 'summary') {
    $url = Url::fromRoute(BookingFlow::ROUTE_NAME, [
      'commerce_order' => $booking->id(),
      'step' => $step,
    ]);
    return new RedirectResponse($url->toString());
  }

}
