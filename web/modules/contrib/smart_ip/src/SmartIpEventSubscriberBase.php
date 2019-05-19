<?php

/**
 * @file
 * Contains \Drupal\smart_ip\SmartIpEventSubscriberBase.
 */

namespace Drupal\smart_ip;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a base class for Smart IP data source modules.
 */
abstract class SmartIpEventSubscriberBase implements EventSubscriberInterface, SmartIpDataSourceInterface {

  /**
   * {@inheritdoc}
   */
  public function includeEditableConfigNames(AdminSettingsEvent $event) {
    $configNames   = $event->getEditableConfigNames();
    $configNames[] = $this->configName();
    $event->setEditableConfigNames($configNames);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents(){
    $events[SmartIpEvents::QUERY_IP][] = ['processQuery'];
    $events[SmartIpEvents::DISPLAY_SETTINGS][]  = ['formSettings'];
    $events[SmartIpEvents::VALIDATE_SETTINGS][] = ['validateFormSettings'];
    $events[SmartIpEvents::SUBMIT_SETTINGS][]   = ['submitFormSettings'];
    $events[SmartIpEvents::GET_CONFIG_NAME][]   = ['includeEditableConfigNames'];
    $events[SmartIpEvents::MANUAL_UPDATE][]     = ['manualUpdate'];
    $events[SmartIpEvents::CRON_RUN][]          = ['cronRun'];
    return $events;
  }

}
