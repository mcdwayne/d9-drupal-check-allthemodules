<?php

namespace Drupal\switches;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\switches\Exception\MissingSwitchException;

/**
 * The switch manager service for interacting with switches.
 *
 * This service should be used for most interactions with switches throughout
 * the system.
 */
class SwitchManager implements SwitchManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The storage handler for switches.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $switchStorage;

  /**
   * The logger channel for reporting important Switch events.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * SwitchManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   The logger channel for reporting important Switch events.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelInterface $loggerChannel) {
    $this->entityTypeManager = $entityTypeManager;
    $this->switchStorage = $entityTypeManager->getStorage('switch');
    $this->logger = $loggerChannel;
  }

  /**
   * {@inheritdoc}
   */
  public function getSwitch($switch_id) {
    $switch = $this->switchStorage->load($switch_id);

    if (is_null($switch)) {
      throw new MissingSwitchException('Attempted to load a missing Switch: ' . $switch_id);
    }

    return $switch;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivationStatus($switch_id) {
    try {
      return $this->getSwitch($switch_id)->getActivationStatus();
    }
    catch (MissingSwitchException $exception) {
      // Log this as a warning for follow-up.
      $this->logger->warning($exception->getMessage());

      // Return the default activation status for missing switches.
      return $this->getDefaultStatus();
    }
  }

  /**
   * Get the default status for missing or disabled switches.
   *
   * @return bool
   *   The default status for missing or disabled switches.
   */
  protected function getDefaultStatus() {
    // @todo Make the default Switch value configurable.
    return FALSE;
  }

}
