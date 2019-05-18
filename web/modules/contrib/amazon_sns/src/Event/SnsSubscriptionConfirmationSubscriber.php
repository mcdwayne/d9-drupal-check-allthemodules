<?php

namespace Drupal\amazon_sns\Event;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber that confirms inbound notification requests.
 *
 * All valid requests from SNS are confirmed automatically.
 */
class SnsSubscriptionConfirmationSubscriber implements ContainerInjectionInterface, EventSubscriberInterface {

  /**
   * The HTTP client used to confirm the subscription with Amazon.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The system logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SnsEvents::SUBSCRIPTION_CONFIRMATION => 'confirm',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.channel.amazon_sns')
    );
  }

  /**
   * Construct a new subscriber for SNS confirmations.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client used to confirm the subscription.
   * @param \Psr\Log\LoggerInterface $logger
   *   The system logger.
   */
  public function __construct(ClientInterface $client, LoggerInterface $logger) {
    $this->client = $client;
    $this->logger = $logger;
  }

  /**
   * Confirm a new subscription.
   *
   * @param \Drupal\amazon_sns\Event\SnsMessageEvent $event
   *   The subscription request message.
   */
  public function confirm(SnsMessageEvent $event) {
    // We explicitly don't catch any HTTP exceptions here. In this case, this
    // means AWS has sent us a bad notification, and our controller will return
    // an error code to AWS so it can retry if needed.
    $message = $event->getMessage();
    $this->client->request('GET', $message['SubscribeURL']);
    $this->logger->info('Subscription confirmed for topic %topic.', [
      '%topic' => $message['TopicArn'],
    ]);
  }

}
