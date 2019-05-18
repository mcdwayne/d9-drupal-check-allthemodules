<?php

namespace Drupal\og_sm_path;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_config\Config\SiteConfigFactoryOverrideInterface;
use Drupal\og_sm_path\Event\SitePathEvent;
use Drupal\og_sm_path\Event\SitePathEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A manager to process site paths.
 */
class SitePathManager implements SitePathManagerInterface {

  /**
   * The path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * The site configuration override service.
   *
   * @var \Drupal\og_sm_config\Config\SiteConfigFactoryOverrideInterface
   */
  protected $configFactoryOverride;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The cache tag invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $invalidator;

  /**
   * Constructs a SitePathManager object.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The path alias storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Drupal\og_sm_config\Config\SiteConfigFactoryOverrideInterface $config_factory_override
   *   The site configuration override service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   *   The cache tag invalidator service.
   */
  public function __construct(AliasStorageInterface $alias_storage, LanguageManagerInterface $language_manager, SiteManagerInterface $site_manager, SiteConfigFactoryOverrideInterface $config_factory_override, EventDispatcherInterface $event_dispatcher, Connection $connection, CacheTagsInvalidatorInterface $invalidator) {
    $this->aliasStorage = $alias_storage;
    $this->languageManager = $language_manager;
    $this->siteManager = $site_manager;
    $this->configFactoryOverride = $config_factory_override;
    $this->eventDispatcher = $event_dispatcher;
    $this->connection = $connection;
    $this->invalidator = $invalidator;

  }

  /**
   * {@inheritdoc}
   */
  public function getPathFromSite(NodeInterface $site) {
    $config = $this->configFactoryOverride->getOverride($site, 'site_settings');
    return $config->get('path');
  }

  /**
   * {@inheritdoc}
   */
  public function lookupPathAlias($path) {
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $path_alias = $this->aliasStorage->lookupPathAlias($path, $langcode);

    if (!$path_alias) {
      $source = $this->aliasStorage->lookupPathSource($path, $langcode);
      $path_alias = $source ? $path : $path_alias;
    }
    return $path_alias;
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteFromPath($path) {
    foreach ($this->siteManager->getAllSites() as $site) {
      if ($this->getPathFromSite($site) === $path) {
        return $site;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSiteAliases(NodeInterface $site) {
    $path = $this->getPathFromSite($site);
    if (!$path) {
      return;
    }

    $path = $this->connection->escapeLike($path) . '/%';

    $select = $this->connection->select(AliasStorage::TABLE);
    $select->condition('source', $path, 'LIKE');
    $select->fields(AliasStorage::TABLE, ['pid', 'source']);
    $path_ids = (array) $select->execute()->fetchAllKeyed();

    $tags = [];
    foreach ($path_ids as $pid => $source) {
      // Try to find the route parameters from the path source so we can use
      // them to construct cache tags which should be invalidated.
      // @todo: Remove once https://www.drupal.org/node/2480077 is fixed.
      $url = Url::fromUserInput($source);
      if (!$url->isRouted()) {
        continue;
      }

      foreach ($url->getRouteParameters() as $name => $value) {
        $tag = $name . ':' . $value;
        $tags[$tag] = $tag;
      }
      $this->aliasStorage->delete(['pid' => $pid]);
    }
    $this->invalidator->invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setPath(NodeInterface $site, $path, $trigger_event = TRUE) {
    $config = $this->configFactoryOverride->getOverride($site, 'site_settings');
    $original_path = $config->get('path');
    if ($original_path === $path) {
      // No change.
      return;
    }

    // Change the path variable.
    $config->set('path', $path)->save();

    // Trigger the path change event.
    if ($trigger_event) {
      $event = new SitePathEvent($site, $original_path, $path);
      $this->eventDispatcher->dispatch(SitePathEvents::CHANGE, $event);
    }
  }

}
