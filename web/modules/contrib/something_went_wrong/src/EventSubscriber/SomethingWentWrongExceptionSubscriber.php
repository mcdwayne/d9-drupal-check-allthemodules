<?php

namespace Drupal\something_went_wrong\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class SomethingWentWrongExceptionSubscriber.
 *
 * @package Drupal\something_went_wrong\EventSubscriber
 */
class SomethingWentWrongExceptionSubscriber implements EventSubscriberInterface {

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $moduleConfig;

  /**
   * The site config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $siteConfig;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $managerMail;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * SomethingWentWrongExceptionSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Mail\MailManagerInterface $manager_mail
   *   The mail manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MailManagerInterface $manager_mail, LoggerChannelFactoryInterface $logger, LanguageManagerInterface $language_manager) {
    $this->moduleConfig = $config_factory->get('something_went_wrong.settings');
    $this->siteConfig = $config_factory->get('system.site');
    $this->managerMail = $manager_mail;
    $this->logger = $logger->get('something_went_wrong');
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['onException', 60];
    return $events;
  }

  /**
   * Exceptions handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
   *   The event to process.
   */
  public function onException(GetResponseForExceptionEvent $event) {
    $exception = $event->getException();
    $class_name = get_class($exception);
    $ignore_list = explode(',', $this->moduleConfig->get('ignore_list'));

    $send = TRUE;

    foreach ($ignore_list as $item) {
      if ($item && strpos($class_name, $item) !== FALSE) {
        $send = FALSE;
        break;
      }
    }

    if ($send) {
      $message = $this->buildMessage($exception);

      if ($this->moduleConfig->get('slack')) {
        $this->sendMessageToSlack($message);
      }

      if ($this->moduleConfig->get('mail')) {
        $this->sendMessageToMail($message);
      }
    }
  }

  /**
   * Builds message.
   *
   * @param \Exception $exception
   *
   * @return string
   */
  protected function buildMessage(\Exception $exception) {
    $class_name = get_class($exception);
    $exception_message = $exception->getMessage();
    $exception_file = $exception->getFile();
    $exception_line = $exception->getLine();

    return sprintf("*Uncaught PHP Exception* \n Class name: %s \n Message: %s \n File: %s \n Line: %s", $class_name, $exception_message, $exception_file, $exception_line);
  }

  /**
   * Sends message to Slack.
   *
   * @param $message
   */
  protected function sendMessageToSlack($message) {
    $label = $this->moduleConfig->get('label');
    $webhook_url = $this->moduleConfig->get('webhook_url');

    if ($webhook_url) {
      $client = new Client();

      try {
        $client->post($webhook_url, [
          'body' => json_encode([
            'text' => $message,
            'username' =>  $label ?: 'Something Went Wrong Bot',
          ]),
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Error while sending Slack message. Error: ' . $e->getMessage());
      }
    }
  }

  /**
   * Sends message to mail.
   *
   * @param $message
   */
  protected function sendMessageToMail($message) {
    $mail_custom_address = $this->moduleConfig->get('mail_custom_address');

    $email = NULL;
    if ($mail_custom_address){
      $email = $mail_custom_address;
    }
    else {
      $email = $this->siteConfig->get('mail');
    }

    if ($email){
      $params['message'] = $message;
      $language = $this->languageManager->getCurrentLanguage()->getId();
      $this->managerMail->mail('something_went_wrong', 'sww_error_mail', $email, $language, $params, NULL, TRUE);
    }
  }

}
