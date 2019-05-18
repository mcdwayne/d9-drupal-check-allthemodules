<?php

namespace Drupal\atm\EventSubscriber;

use Drupal\atm\AtmHttpClient;
use Drupal\atm\Helper\AtmApiHelper;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class DefaultSubscriber.
 *
 * @package Drupal\atm
 */
class DefaultSubscriber implements EventSubscriberInterface {

  /**
   * AtmApiHelper.
   *
   * @var \Drupal\atm\Helper\AtmApiHelper
   */
  protected $helper;

  /**
   * AtmHttpClient.
   *
   * @var \Drupal\atm\AtmHttpClient
   */
  protected $httpClient;

  /**
   * DefaultSubscriber constructor.
   *
   * @param \Drupal\atm\AtmHttpClient $atmHttpClient
   *   AtmHttpClient.
   * @param \Drupal\atm\Helper\AtmApiHelper $atmApiHelper
   *   AtmApiHelper.
   */
  public function __construct(AtmHttpClient $atmHttpClient, AtmApiHelper $atmApiHelper) {
    $this->httpClient = $atmHttpClient;
    $this->helper = $atmApiHelper;
  }

  /**
   * Return AtmApiHelper.
   *
   * @return \Drupal\atm\Helper\AtmApiHelper
   *   Return AtmApiHelper.
   */
  protected function getHelper() {
    return $this->helper;
  }

  /**
   * Get service AtmHttpClient.
   *
   * @return \Drupal\atm\AtmHttpClient
   *   Get service AtmHttpClient.
   */
  public function getHttpClient() {
    return $this->httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'onModuleInit',
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  /**
   * Init. Check if api exists.
   */
  public function onModuleInit($events) {
    $timeLimit = ini_get('max_execution_time');
    set_time_limit(0);

    $this->getHelper()->propertyCreate();

    $themeConfigId = $this->getHelper()->getThemeConfig()->get('theme-config-id');
    if (!$themeConfigId) {

      $isThemeRetrieved = $this->getHttpClient()->retrieveThemeConfig();
      if ($isThemeRetrieved !== TRUE) {
        $this->getHelper()->createThemeConfig();
      }
    }

    set_time_limit($timeLimit);
  }

  /**
   * Event fired when saving a configuration object.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $events
   *   Configuration event for event listeners.
   */
  public function onConfigSave(ConfigCrudEvent $events) {
    $config = $events->getConfig();

    if ($config->getName() == 'system.theme') {
      if ($events->isChanged('default')) {
        $this->getHttpClient()->propertyCreate();

        $themeConfigId = $this->getHelper()->getThemeConfig()->get('theme-config-id');
        if (!$themeConfigId) {

          $isThemeRetrieved = $this->getHttpClient()->retrieveThemeConfig();
          if ($isThemeRetrieved !== TRUE) {
            $this->getHelper()->createThemeConfig();
          }
        }
      }
    }
  }

}
