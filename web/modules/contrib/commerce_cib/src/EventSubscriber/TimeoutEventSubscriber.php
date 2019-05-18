<?php

namespace Drupal\commerce_cib\EventSubscriber;

use Drupal\commerce_cib\Event\CibEvents;
use Drupal\commerce_cib\Event\Timeout;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimeoutEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
  public function __construct(MailManagerInterface $mail_manager, MessengerInterface $messenger, LanguageManagerInterface $language_manager, TranslationInterface $string_translation, ConfigFactoryInterface $config) {
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
      CibEvents::TIMEOUT => 'informCustomer',
    ];
    return $events;
  }

  /**
   * Notifies customer when payment timeouts.
   *
   * @param \Drupal\commerce_cib\Event\Timeout_$event
   *   The timout event.
   */
  public function informCustomer(Timeout $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $payment = $event->getPayment();
    $email = $payment->getOrder()->getEmail();
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $site_name = $this->config->get('system.site')->get('name');
    $site_url = \Drupal::request()->getSchemeAndHttpHost();
    $vars = [
      '@site_name' => $site_name,
      '@site_url' => $site_url,
      '@order_id' => $payment->getOrderId(),
      '@remote_id' => $payment->getRemoteId(),
      '@rc' => $payment->payment_cib_rc->value,
      '@rt' => $payment->payment_cib_rt->value,
      '@anum' => $payment->payment_cib_anum->value,
      '@amount' => $payment->getAmount(),
      '@email' => $email,
    ];

    $body = $this->t("Dear Customer,\r\nYou are receiving this email because your email address (@email)  was given in an order at [@site_url] with transaction ID [@remote_id].\r\n\r\nWe apologize but your transaction did not get through. The error was the following:\r\n[@rt]\r\n\r\nImportant\r\nIf your card is processed during the transaction, the payment processor is automatically reversing your transaction within 10 minutes.\r\n\r\nThe products are kept in your shopping cart at [@site_name]. \r\n\r\nThe transaction id is [@remote_id].\r\nThe response code is [@rc] .\r\nThe permission number is [@anum].\r\nThe paid amount is [@amount].\r\nWe apologize for the inconvenience.\r\n\r\n-- [@site_name]  team", $vars);
    $params = [
      'subject' => $this->t('Order on @site_name.', $vars),
      'body' => $body,
    ];
    $this->mailManager->mail('commerce_cib', 'timeout', $email, $langcode, $params);
    $message = $this->t('The transaction id is: @remote_id.<br/>The response code is: @rc.<br/>The response message is: @rt.<br/><br/>The permission number is: @anum.<br/>The paid amount is: @amount.<br/><br/>Thank you for your purchase! An email has been sent to @email with the details.', $vars);
    $this->messenger->addMessage($message);
  }

}
