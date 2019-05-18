<?php

namespace Drupal\micro_path;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\micro_site\Entity\SiteInterface;

/**
 * Provides a utility for creating a unique path alias per micro site.
 */
class SiteAliasUniquifier implements SiteAliasUniquifierInterface {

  /**
   * Alias schema max length.
   *
   * @var int
   */
  protected $aliasSchemaMaxLength = 255;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The route provider service.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface.
   */
  protected $routeProvider;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new AliasUniquifier.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, RouteProviderInterface $route_provider, AliasManagerInterface $alias_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->routeProvider = $route_provider;
    $this->aliasManager = $alias_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function uniquify(&$alias, $source, $site_id, $langcode) {
    $config = $this->configFactory->get('micro_path.settings');

    if (!$this->isReserved($alias, $source, $site_id, $langcode)) {
      return;
    }

    // If the alias already exists, generate a new, hopefully unique, variant.
    $max_length = min($config->get('max_length'), $this->getAliasSchemaMaxlength());
    $separator = '-';
    $original_alias = $alias;

    $i = 0;
    do {
      // Append an incrementing numeric suffix until we find a unique alias.
      $unique_suffix = $separator . $i;
      $alias = Unicode::truncate($original_alias, $max_length - Unicode::strlen($unique_suffix), TRUE) . $unique_suffix;
      $i++;
    } while ($this->isReserved($alias, $source, $site_id, $langcode));
  }

  /**
   * {@inheritdoc}
   */
  public function isReserved($alias, $source, $site_id, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    // Check if this alias already exists per micro site.
    $properties = [
      'alias' => $alias,
      'language' => $langcode,
      'site_id' => $site_id,
    ];
    $existing_micro_path = NULL;
    $existing_source = NULL;
    $microPathStorage = $this->entityTypeManager->getStorage('micro_path');
    $existing_micro_paths = $microPathStorage->loadByProperties($properties);
    if ($existing_micro_paths) {
      $existing_micro_path = reset($existing_micro_paths);
    }

    if ($existing_micro_path instanceof MicroPathInterface) {
      $existing_source = $existing_micro_path->getSource();
    }

    if ($existing_source) {
      if ($existing_source != $alias) {
        // If it is an alias for the provided source, it is allowed to keep using
        // it. If not, then it is reserved.
        return $existing_source != $source;
      }

    }

    // Then check if there is a route with the same path.
    if ($this->isRoute($alias)) {
      return TRUE;
    }
    // Finally check if any other modules have reserved the alias.
    $args = array(
      $alias,
      $source,
      $site_id,
      $langcode,
    );
    $implementations = $this->moduleHandler->getImplementations('micro_path_is_site_alias_reserved');
    foreach ($implementations as $module) {

      $result = $this->moduleHandler->invoke($module, 'micro_path_is_site_alias_reserved', $args);

      if (!empty($result)) {
        // As soon as the first module says that an alias is in fact reserved,
        // then there is no point in checking the rest of the modules.
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Verify if the given path is a valid route.
   *
   * @param string $path
   *   A string containing a relative path.
   *
   * @return bool
   *   TRUE if the path already exists.
   *
   * @throws \InvalidArgumentException
   */
  public function isRoute($path) {
    if (is_file(DRUPAL_ROOT . '/' . $path) || is_dir(DRUPAL_ROOT . '/' . $path)) {
      // Do not allow existing files or directories to get assigned an automatic
      // alias. Note that we do not need to use is_link() to check for symbolic
      // links since this returns TRUE for either is_file() or is_dir() already.
      return TRUE;
    }

    $routes = $this->routeProvider->getRoutesByPattern($path);

    // Only return true for an exact match, ignore placeholders.
    foreach ($routes as $route) {
      if ($route->getPath() == $path) {
        return TRUE;
      }
    }

    return FALSE;

  }

  /**
   * {@inheritdoc}
   */
  public function getAliasSchemaMaxLength() {
    return $this->aliasSchemaMaxLength;
  }

}
