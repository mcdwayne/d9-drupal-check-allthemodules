<?php

namespace Drupal\acquia_contenthub\EventSubscriber\ClientCdf;

use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\Event\BuildClientCdfEvent;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds base url to all CDF documents.
 */
class ClientCdfLanguage implements EventSubscriberInterface {

  /**
   * Entity Type Manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Module handler
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * ClientManagerFactory constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *  The Entity Type Manager.
   */
  public function __construct(EntityTypeManager $entityTypeManager, ModuleHandler $moduleHandler) {
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::BUILD_CLIENT_CDF][] = ['onBuildClientCdf', 100];
    return $events;
  }

  /**
   * Adds language information to ClientCDF documents.
   *
   * @param \Drupal\acquia_contenthub\Event\BuildClientCdfEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onBuildClientCdf(BuildClientCdfEvent $event) {
    if(!$this->moduleHandler->moduleExists('language')) {
      return;
    }

    $cdf = $event->getCdf();
    $metadata = $cdf->getMetadata();
    $metadata['languages'] = $this->getLanguages();
    $cdf->setMetadata($metadata);
  }

  /**
   * Get languages from the configurable language entity type manager.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLanguages() {
    $languages = [];
    $lang_entities = $this->entityTypeManager->getStorage('configurable_language')->loadMultiple();
    foreach ($lang_entities as $langcode => $language) {
      $languages[$langcode] = $language->toArray();
      // Cleanup Extra lines for efficient storage in Plexus.
      unset($languages[$langcode]['_core'], $languages[$langcode]['dependencies']);
    }
    return $languages;
  }
}
