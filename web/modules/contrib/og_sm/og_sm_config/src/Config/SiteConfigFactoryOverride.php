<?php

namespace Drupal\og_sm_config\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigCollectionInfo;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigFactoryOverrideBase;
use Drupal\Core\Config\ConfigRenameEvent;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\og_sm\Event\SiteEvent;
use Drupal\og_sm\Event\SiteEvents;
use Drupal\og_sm\OgSm;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Provides site overrides for the configuration factory.
 */
class SiteConfigFactoryOverride extends ConfigFactoryOverrideBase implements SiteConfigFactoryOverrideInterface {

  use SiteConfigCollectionNameTrait;

  /**
   * The configuration storage.
   *
   * Do not access this directly. Should be accessed through self::getStorage()
   * so that the cache of storages per langcode is used.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $baseStorage;

  /**
   * An array of configuration storages keyed by langcode.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $storages;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The site node used to override configuration data.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $site;

  /**
   * Constructs the LanguageConfigFactoryOverride object.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage engine.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfig
   *   The typed configuration manager.
   */
  public function __construct(StorageInterface $storage, TypedConfigManagerInterface $typedConfig) {
    $this->baseStorage = $storage;
    $this->typedConfigManager = $typedConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function getSite() {
    return $this->site;
  }

  /**
   * {@inheritdoc}
   */
  public function setSite(NodeInterface $site = NULL) {
    $this->site = $site;
  }

  /**
   * {@inheritdoc}
   */
  public function addCollections(ConfigCollectionInfo $collectionInfo) {
    foreach (OgSm::siteManager()->getAllSites() as $site) {
      $collectionInfo->addCollection($this->createConfigCollectionName($site), $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOverride(NodeInterface $site, $name) {
    $storage = $this->getStorage($site);
    $data = $storage->read($name);

    $override = new SiteConfigOverride(
      $name,
      $storage,
      $this->typedConfigManager
    );

    if (!empty($data)) {
      $override->initWithData($data);
    }
    return $override;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage(NodeInterface $site) {
    $collectionName = $this->createConfigCollectionName($site);
    if (!isset($this->storages[$collectionName])) {
      $this->storages[$collectionName] = $this->baseStorage->createCollection($collectionName);
    }
    return $this->storages[$collectionName];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[KernelEvents::REQUEST][] = ['onKernelRequestSetSite'];
    // Set the priority of the delete event low, we only want to remove the
    // config at the last possible moment so other modules can still use it
    // during site cleanup.
    $events[SiteEvents::DELETE][] = ['onSiteDelete', -255];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    foreach (OgSm::siteManager()->getAllSites() as $site) {
      $config_override = $this->getOverride($site, $name);
      if (!$config_override->isNew()) {
        $this->filterOverride($config, $config_override);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigDelete(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    foreach (OgSm::siteManager()->getAllSites() as $site) {
      $config_override = $this->getOverride($site, $name);
      if (!$config_override->isNew()) {
        $config_override->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onConfigRename(ConfigRenameEvent $event) {
    $config = $event->getConfig();
    $name = $config->getName();
    $old_name = $event->getOldName();
    foreach (OgSm::siteManager()->getAllSites() as $site) {
      $config_override = $this->getOverride($site, $old_name);
      if (!$config_override->isNew()) {
        $saved_config = $config_override->get();
        $storage = $this->getStorage($site);
        $storage->write($name, $saved_config);
        $config_override->delete();
      }
    }
  }

  /**
   * Sets the default site when the request dispatching has started.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestSetSite(GetResponseEvent $event) {
    if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
      return;
    }
    $currentSite = OgSm::siteManager()->currentSite();
    if ($currentSite) {
      $this->setSite($currentSite);
    }
  }

  /**
   * Removes the site override collection when the site has been deleted.
   *
   * @param \Drupal\og_sm\Event\SiteEvent $event
   *   The site Event.
   */
  public function onSiteDelete(SiteEvent $event) {
    $this->getStorage($event->getSite())->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    if ($this->site) {
      $storage = $this->getStorage($this->site);
      return $storage->readMultiple($names);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return $this->site ? $this->site->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $siteId = $this->getSiteIdFromCollectionName($collection);
    return $this->getOverride(OgSm::siteManager()->load($siteId), $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();
    if ($this->site) {
      $metadata->setCacheContexts(['og_group_context']);
      $metadata->setCacheTags(['og_sm_config:' . $this->site->id() . ':' . $name]);
    }
    return $metadata;
  }

}
