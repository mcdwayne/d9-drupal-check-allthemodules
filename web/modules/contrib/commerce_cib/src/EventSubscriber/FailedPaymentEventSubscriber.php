<?php

namespace Drupal\commerce_cib\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\commerce_cib\Event\CibEvents;
use Drupal\commerce_cib\Event\FailedPayment;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FailedPaymentEventSubscriber implements EventSubscriberInterface {

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
   * Constructs a new FailedPaymentEventSubscriber object.
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
      CibEvents::FAILED_PAYMENT => 'informCustomer',
    ];
    return $events;
  }

  /**
   * Informs customer when payment fails..
   *
   * @param \Drupal\commerce_cib\Event\FailedPayment $event
   *   The failed payment event.
   */
  public function informCustomer(FailedPayment $event) {
    $payment = $event->getPayment();
    $order = $payment->getOrder();
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

    $body = $this->t("Dear Customer,\r\n\r\nThe transaction id is: @remote_id.\r\nThe response code is: @rc.\r\nThe response message is: @rt.\r\n\r\nSomething went wrong with the order. Please try again!\r\n\r\n@site_name team.", $vars);
    $params = [
      'subject' => $this->t('Unsuccesful order on @site_name.', $vars),
      'body' => $body,
    ];
    $this->mailManager->mail('commerce_cib', 'failed_payment', $order->getEmail(), $langcode, $params);
    $message = $this->t('The transaction id is: @remote_id.<br/>The response code is: @rc.<br/>The response message is: @rt.<br/>An email has been sent to @email with the details.<br/><br/>Something went wrong. Please try again!', $vars);
    $this->messenger->addWarning($message);
    $payment->setState('voided')->save();
  }

}
