<?php

namespace Drupal\fixed_path_alias;

use Drupal\Core\CacheDecorator\CacheDecoratorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * Fixed path alias manager decorator. Wraps the alias manager.
 */
class FixedPathAliasManager implements AliasManagerInterface, CacheDecoratorInterface {

  /**
   * The wrapped alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The alias storage service.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $storage;

  /**
   * Language manager for retrieving the default langcode when none is specified.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The fixed aliases config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The channel logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Holds an array of aliases for which no path was found.
   *
   * @var array
   */
  protected $noPath = [];

  /**
   * Holds an array of paths that have no alias.
   *
   * @var array
   */
  protected $noAlias = [];

  /**
   * Constructs an FixedPathAliasManager.
   *
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager that this class decorates.
   * @param \Drupal\Core\Path\AliasStorageInterface $storage
   *   The alias storage service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(AliasManagerInterface $alias_manager, AliasStorageInterface $storage, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, LoggerInterface $logger) {
    $this->aliasManager = $alias_manager;
    $this->storage = $storage;
    $this->languageManager = $language_manager;
    $this->config = $config_factory->get('fixed_path_alias.aliases');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathByAlias($alias, $langcode = NULL) {
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // If we already know that there are no paths for this alias simply return.
    if (empty($alias) || !empty($this->noPath[$langcode][$alias])) {
      return $alias;
    }

    // Search the path in the wrapped alias manager.
    $path = $this->aliasManager->getPathByAlias($alias, $langcode);

    // If not found, try to look up within the fixed path aliases.
    if ($path !== $alias
      || $path = $this->lookupFixedPath($alias, $langcode, TRUE)) {
      return $path;
    }

    // Not found, cache in no path aliases.
    $this->noPath[$langcode][$alias] = TRUE;
    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasByPath($path, $langcode = NULL) {
    $langcode = $langcode ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId();

    // If we already know that there are no aliases for this path simply return.
    if (!empty($this->noAlias[$langcode][$path])) {
      return $path;
    }

    $alias = $this->aliasManager->getAliasByPath($path, $langcode);

    // Alias not found by the wrapped alias manager, look up in fixed aliases.
    if ($alias !== $path
      || $alias = $this->lookupFixedAlias($path, $langcode, TRUE)) {
      return $alias;
    }

    // Not found, cache in no alias paths.
    $this->noAlias[$langcode][$path] = TRUE;
    return $path;
  }

  /**
   * Resolves a source URL from a given path within the fixed path aliases.
   *
   * @param string $alias
   *   The path alias to look up.
   * @param string $langcode
   *   The language.
   * @param bool $restore
   *   (optional) Restore the regular path alias from the fixed one if found.
   * @return string|false
   *   The source URL for the given alias, FALSE if not found.
   */
  protected function lookupFixedPath($alias, $langcode, $restore = FALSE) {
    $fixed_aliases = $this->config->get('aliases') ?: [];

    // Find a match in the fixed aliases for the given alias.
    foreach ($fixed_aliases as $fixed_alias) {
      if ($alias == $fixed_alias['alias']
        && ($fixed_alias['langcode'] == $langcode || $fixed_alias['langcode'] == LanguageInterface::LANGCODE_NOT_SPECIFIED)) {
        // Restore the path alias.
        if ($restore) {
          $this->restoreAlias($fixed_alias);
        }

        return $fixed_alias['source'];
      }
    }

    return FALSE;
  }

  /**
   * Resolves an alias from a given path within the fixed path aliases.
   *
   * @param string $path
   *   The path to look up.
   * @param string $langcode
   *   The language.
   * @param bool $restore
   *   (optional) Restore the regular path alias from the fixed one if found.
   *
   * @return string|false
   *   The alias for the given path, FALSE if not found.
   */
  protected function lookupFixedAlias($path, $langcode, $restore = FALSE) {
    $fixed_aliases = $this->config->get('aliases') ?: [];

    // Find a match in the fixed aliases for the given path.
    foreach ($fixed_aliases as $fixed_alias) {
      if ($path == $fixed_alias['source']
        && ($fixed_alias['langcode'] == $langcode || $fixed_alias['langcode'] == LanguageInterface::LANGCODE_NOT_SPECIFIED)) {
        // Restore the path alias.
        if ($restore) {
          $this->restoreAlias($fixed_alias);
        }

        return $fixed_alias['alias'];
      }
    }

    return FALSE;
  }

  /**
   * Restores an alias into database from a fixed alias config entry.
   *
   * @param array $fixed_alias
   *   A keyed array with the fixed alias config entry.
   */
  protected function restoreAlias($fixed_alias) {
    if ($new_alias = $this->storage->save($fixed_alias['source'], $fixed_alias['alias'], $fixed_alias['langcode'])) {
      // Log the event.
      $edit_link = Link::createFromRoute('Edit', 'path.admin_edit', ['pid' => $new_alias['pid']])
        ->toString();
      $this->logger->notice('Path alias %alias restored from config.', [
        '%alias' => $fixed_alias['alias'],
        'link' => $edit_link
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear($source = NULL) {
    $this->aliasManager->cacheClear($source);
    $this->noPath = [];
    $this->noAlias = [];
  }

  /**
   * {@inheritdoc}
   */
  public function setCacheKey($key) {
    if ($this->aliasManager instanceof CacheDecoratorInterface) {
      $this->aliasManager->setCacheKey($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function writeCache() {
    if ($this->aliasManager instanceof CacheDecoratorInterface) {
      $this->aliasManager->writeCache();
    }
  }

}
