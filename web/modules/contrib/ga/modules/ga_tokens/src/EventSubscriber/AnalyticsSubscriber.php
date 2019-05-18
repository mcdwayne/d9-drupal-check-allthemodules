<?php

namespace Drupal\ga_tokens\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\ga\AnalyticsCommand\Dimension;
use Drupal\ga\AnalyticsCommand\Metric;
use Drupal\ga\AnalyticsEvents;
use Drupal\ga\Event\CollectEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DefaultCommandSubscriber.
 */
class AnalyticsSubscriber implements EventSubscriberInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AnalyticsEvents::COLLECT => [
        ['onCollectGlobalProperties'],
      ],
    ];
  }

  /**
   * DefaultCommandSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\Core\Utility\Token $token
   *   The Token service.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    Token $token
  ) {
    $this->configFactory = $configFactory;
    $this->token = $token;
  }

  /**
   * Add global dimensions and metrics.
   *
   * @param \Drupal\ga\Event\CollectEvent $event
   *   The AnalyticsEvents::COLLECT event.
   */
  public function onCollectGlobalProperties(CollectEvent $event) {
    $config = $this->configFactory->get('ga_tokens.global');

    $dimensions = $config->get('dimensions') ?: [];
    foreach ($dimensions as $index => $dimension) {
      $value = $this->token->replace($dimension['value']);
      $event->addCommand(new Dimension($index, $value));
    }

    $metrics = $config->get('metrics') ?: [];
    foreach ($metrics as $index => $metric) {
      $value = $this->token->replace($metric['value']);
      $event->addCommand(new Metric($index, $value));
    }
  }

}
