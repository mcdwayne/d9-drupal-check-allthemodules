<?php

namespace Drupal\client_config_care\Subscriber;

use Drupal\client_config_care\Deactivator;
use Drupal\client_config_care\Validator\ArrayDiffer;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class ConfigDelete extends ConfigSubscriberAbstract implements EventSubscriberInterface {

  /**
   * @var string
   */
  private const USER_OPERATION = 'delete';

  public function __construct(LoggerChannelInterface $logger, EntityTypeManager $entityTypeManager, ArrayDiffer $arrayDiffer, Deactivator $deactivator) {
    parent::__construct($arrayDiffer, $logger, self::USER_OPERATION, $entityTypeManager, $deactivator);
  }

	/**
	 * {@inheritdoc}
	 */
	public static function getSubscribedEvents() {
    $events[ConfigEvents::DELETE][] = ['onConfigHandle', 0];
    return $events;
	}

}
