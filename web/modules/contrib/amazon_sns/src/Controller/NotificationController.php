<?php

namespace Drupal\amazon_sns\Controller;

use Aws\Sns\Exception\InvalidSnsMessageException;
use Drupal\amazon_sns\Event\MessageEventDispatcher;
use Drupal\amazon_sns\RequestMessageValidator;
use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for inbound SNS notifications.
 *
 * This controller handles all inbound SNS notifications, including subscription
 * and unsubscription notices. Instead of implementing additional controllers,
 * subscribe to SNS events and check the TopicARN attribute.
 */
class NotificationController implements ContainerInjectionInterface {

  /**
   * The system logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The dispatcher used to fire events.
   *
   * @var \Drupal\amazon_sns\Event\MessageEventDispatcher
   */
  protected $messageEventDispatcher;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('amazon_sns.message_dispatcher'),
      $container->get('logger.channel.amazon_sns')
    );
  }

  /**
   * Construct a new NotificationController.
   *
   * @param \Drupal\amazon_sns\Event\MessageEventDispatcher $messageEventDispatcher
   *   The event dispatcher.
   * @param \Psr\Log\LoggerInterface $logger
   *   The system logger.
   */
  public function __construct(MessageEventDispatcher $messageEventDispatcher, LoggerInterface $logger) {
    $this->messageEventDispatcher = $messageEventDispatcher;
    $this->logger = $logger;
  }

  /**
   * Controller callback for inbound SNS notifications.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The full HTTP request with the SNS notification.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response indicating to Amazon SNS if the notification was successful or
   *   if it needs to be resent at a later time.
   */
  public function receive(Request $request) {
    // We cast corrupt or invalid data exceptions to HTTP 400.
    try {
      $message = RequestMessageValidator::getMessageFromRequest($request);
    }
    catch (InvalidSnsMessageException $e) {
      // The SNS message signature failed.
      return $this->badRequestResponse($e);
    }
    catch (\InvalidArgumentException $e) {
      // The message was missing a required key.
      return $this->badRequestResponse($e);
    }

    $this->messageEventDispatcher->dispatch($message);

    // We've successfully processed the response, so return a 200 so SNS doesn't
    // retry the notification.
    return new Response();
  }

  /**
   * Helper to log an exception to the watchdog.
   *
   * @param \Exception $exception
   *   The exception that is going to be logged.
   * @param string $message
   *   The message to store in the log. If empty, a text that contains all
   *   useful information about the passed-in exception is used.
   * @param array $variables
   *   Array of variables to replace in the message on display or
   *   NULL if message is already translated or not possible to
   *   translate.
   * @param int $severity
   *   The severity of the message, as per RFC 3164.
   * @param string $link
   *   A link to associate with the message.
   *
   * @see watchdog_exception
   */
  private function watchdogException(\Exception $exception, $message = NULL, array $variables = [], $severity = RfcLogLevel::ERROR, $link = NULL) {
    // Use a default value if $message is not set.
    if (empty($message)) {
      $message = '%type: @message in %function (line %line of %file).';
    }

    if ($link) {
      $variables['link'] = $link;
    }

    $variables += Error::decodeException($exception);

    $this->logger->log($severity, $message, $variables);
  }

  /**
   * Log a bad request and cast the exception to a HTTP 4XX client error.
   *
   * @param \Exception $e
   *   The exception that indicated a client error.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A 4XX response, containing the escaped exception message.
   */
  private function badRequestResponse(\Exception $e) {
    $this->watchdogException($e);
    return new Response(Html::escape($e->getMessage()), Response::HTTP_BAD_REQUEST);
  }

}
