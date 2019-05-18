<?php

namespace Drupal\apptiles\ApplicationTiles;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of default application tiles manager.
 */
class Manager implements ManagerInterface {

  /**
   * Name of theme.
   *
   * @var string
   */
  protected $themeName = '';
  /**
   * Path to theme.
   *
   * @var string
   */
  protected $themePath = '';
  /**
   * Application tiles settings.
   *
   * @var array
   */
  protected $settings = [];
  /**
   * An instance of the "config.factory" service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * An instance of the "theme_handler" service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;
  /**
   * An instance of the "cache.default" service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;
  /**
   * An instance of the "router.admin_context" service.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ThemeHandlerInterface $theme_handler,
    CacheBackendInterface $cache_backend,
    AdminContext $admin_context
  ) {
    $this->configFactory = $config_factory;
    $this->themeHandler = $theme_handler;
    $this->cacheBackend = $cache_backend;
    $this->adminContext = $admin_context;

    $this->themeName = $theme_handler->getDefault();
    $this->themePath = drupal_get_path('theme', $this->themeName);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme_handler'),
      $container->get('cache.default'),
      $container->get('router.admin_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    if (empty($this->settings)) {
      $theme = $this->themeHandler->getTheme($this->themeName);
      // Use global config because "theme_get_setting()" could possibly
      // override it. Execution of the next construction will break the
      // result:
      // @code
      // \Drupal::configFactory()
      //   ->getEditable('bartik.settings')
      //   ->set(APPTILES_MODULE_NAME, [])
      //   ->save(TRUE);
      // @endcode
      $this->settings = (array) $this->configFactory
        ->get('system.theme.global')
        ->get(APPTILES_MODULE_NAME);

      if (!empty($theme->info[APPTILES_MODULE_NAME]) && is_array($theme->info[APPTILES_MODULE_NAME])) {
        $this->settings = array_merge($this->settings, $theme->info[APPTILES_MODULE_NAME]);
      }
    }

    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($setting, $default_value = NULL) {
    // Initialize, probably, uninitialized settings.
    $this->getSettings();

    return isset($this->settings[$setting]) ? $this->settings[$setting] : $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return "$this->themePath/tiles";
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls() {
    $path = $this->getPath();
    $cache = $this->cacheBackend->get($path);

    if ($cache === FALSE) {
      $settings = [];

      foreach (static::TYPES as $os) {
        foreach (file_scan_directory("$path/$os", '/^\d+x\d+\.' . APPTILES_IMAGE_EXTENSION . '$/', ['recurse' => FALSE]) as $file) {
          $settings[$os][$file->name] = Url::fromUri("internal:/$file->uri")->setAbsolute()->toString();
        }
      }

      $this->cacheBackend->set($path, $settings);
    }
    else {
      $settings = $cache->data;
    }

    foreach (static::TYPES as $os) {
      $settings += [$os => []];
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return !empty($this->getSetting('allowed_for_admin_theme')) || !$this->adminContext->isAdminRoute();
  }

}
