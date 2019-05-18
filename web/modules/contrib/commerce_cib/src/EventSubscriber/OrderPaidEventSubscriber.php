<?php

namespace Drupal\commerce_cib\EventSubscriber;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderEvent;

class OrderPaidEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface;
   */
  protected $languageManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs a new OrderEventSubscriber object.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MailManagerInterface $mail_manager, MessengerInterface $messenger, LanguageManagerInterface $language_manager, TranslationInterface $string_translation, ConfigFactoryInterface $config) {
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
    $this->languageManager = $language_manager;
    $this->stringTranslation = $string_translation;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      OrderEvents::ORDER_PAID => 'informCustomer',
    ];
    return $events;
  }

  /**
   * Finalizes the cart when the order is placed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function informCustomer(OrderEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    $payment_ids = $this->entityTypeManager->getStorage('commerce_payment')->getQuery()
      ->condition('order_id', $order->id())
      ->sort('payment_id', 'DESC')
      ->range(0, 1)
      ->execute();
    $payment_id = reset($payment_ids);
    $payment = Payment::load($payment_id);
    if ($payment && $payment->bundle() === 'payment_cib') {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      $site_name = $this->config->get('system.site')->get('name');
      $vars = [
        '@site_name' => $site_name,
        '@order_id' => $order->id(),
        '@remote_id' => $payment->getRemoteId(),
        '@rc' => $payment->payment_cib_rc->value,
        '@rt' => $payment->payment_cib_rt->value,
        '@anum' => $payment->payment_cib_anum->value,
        '@amount' => $payment->getAmount(),
        '@email' => $order->getEmail(),
      ];

      $body= $this->t("Dear Customer,\r\nThe transaction id is: @remote_id.\r\nThe response code is: @rc.\r\nThe response message is: @rt.\r\n\r\nThe permission number is: @anum.\r\nThe paid amount is: @amount.\r\n\r\nThank you for your purchase!\r\n@site_name team.", $vars);
      $params = [
        'subject' => $this->t('Order on @site_name.', $vars),
        'body' => $body,
      ];
      $this->mailManager->mail('commerce_cib', 'order_placed', $order->getEmail(), $langcode, $params);
      $message = $this->t('The transaction id is: @remote_id.<br/>The response code is: @rc.<br/>The response message is: @rt.<br/><br/>The permission number is: @anum.<br/>The paid amount is: @amount.<br/><br/>Thank you for your purchase! An email has been sent to @email with the details.', $vars);
      $this->messenger->addMessage($message);
    }
  }

}
