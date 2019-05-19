<?php

namespace Drupal\ultimenu;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Ultimenu skins utility methods.
 */
class UltimenuSkin extends UltimenuBase implements UltimenuSkinInterface {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The info parser service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Static cache for the skin path.
   *
   * @var array
   */
  protected $skinPath;

  /**
   * Static cache of skins.
   *
   * @var array
   */
  protected $skins;

  /**
   * Static cache of libraries.
   *
   * @var array
   */
  protected $libraries;

  /**
   * The cache key.
   *
   * @var string
   */
  protected $cacheKey = 'ultimenu';

  /**
   * Constructs a Ultimenu object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, BlockManagerInterface $block_manager, ThemeHandlerInterface $theme_handler, FileSystemInterface $file_system, CacheBackendInterface $cache_backend) {
    parent::__construct($config_factory, $entity_type_manager, $block_manager);
    $this->themeHandler = $theme_handler;
    $this->fileSystem = $file_system;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.block'),
      $container->get('theme_handler'),
      $container->get('file_system'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($uri) {
    if (!isset($this->skinPath[md5($uri)])) {
      list(, $skin_name) = array_pad(array_map('trim', explode("|", $uri, 2)), 2, NULL);

      if (strpos($uri, "module|") !== FALSE) {
        $skin_path = 'css/theme/' . $skin_name . '.css';
      }
      elseif (strpos($uri, "custom|") !== FALSE) {
        $path = $this->getSetting('skins');
        $skin_path = '/' . $path . '/' . $skin_name . '.css';
      }
      elseif (strpos($uri, "theme|") !== FALSE) {
        $theme_default = $this->getConfig('system.theme')->get('default');
        $path = drupal_get_path('theme', $theme_default) . '/css/ultimenu';
        $skin_path = '/' . $path . '/' . $skin_name . '.css';
      }

      $this->skinPath[md5($uri)] = isset($skin_path) ? $skin_path : '';
    }
    return $this->skinPath[md5($uri)];
  }

  /**
   * {@inheritdoc}
   */
  public function getName($path) {
    $skin_name     = $this->fileSystem->basename($path, '.css');
    $skin_basename = str_replace("ultimenu--", "", $skin_name);

    return str_replace("-", "_", $skin_basename);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple() {
    if (!isset($this->skins)) {
      if ($cache = $this->cacheBackend->get($this->cacheKey . ':skin')) {
        $this->skins = $cache->data;
      }
      else {
        $theme_default = $this->getConfig('system.theme')->get('default');
        $theme_skin    = drupal_get_path('theme', $theme_default) . '/css/ultimenu';
        $custom_skin   = $this->getSetting('skins');
        $module_skin   = drupal_get_path('module', 'ultimenu') . '/css/theme';
        $mask          = '/.css$/';

        $files = [];
        if (is_dir($module_skin)) {
          foreach (file_scan_directory($module_skin, $mask) as $filename => $file) {
            $files[$filename] = $file;
          }
        }
        if (!empty($custom_skin) && is_dir($custom_skin)) {
          foreach (file_scan_directory($custom_skin, $mask) as $filename => $file) {
            $files[$filename] = $file;
          }
        }
        if (is_dir($theme_skin)) {
          foreach (file_scan_directory($theme_skin, $mask) as $filename => $file) {
            $files[$filename] = $file;
          }
        }
        if ($files) {
          $skins = [];
          foreach ($files as $file) {
            $uri = $file->uri;
            $name = $file->name;

            // Simplify lengthy deep directory structure.
            if (strpos($uri, $module_skin) !== FALSE) {
              $uri = "module|" . $name;
            }
            // Fix for Warning: Empty needle.
            elseif (!empty($custom_skin) && strpos($uri, $custom_skin) !== FALSE) {
              $uri = "custom|" . $name;
            }
            elseif (is_dir($theme_skin) && strpos($uri, $theme_skin) !== FALSE) {
              $uri = "theme|" . $name;
            }

            // Convert file name to CSS friendly for option label and styling.
            $skins[$uri] = Html::cleanCssIdentifier(mb_strtolower($name));
          }

          ksort($skins);
          $this->cacheBackend->set($this->cacheKey . ':skin', $skins, Cache::PERMANENT, ['skin']);

          $this->skins = $skins;
        }
      }
    }
    return $this->skins;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions($all = FALSE) {
    // Invalidate the theme cache to update ultimenu region-based theme.
    $this->themeHandler->refreshInfo();

    if ($all) {
      // Clear the skins cache.
      $this->skins = NULL;
      // Invalidate the block cache to update ultimenu-based derivatives.
      $this->blockManager->clearCachedDefinitions();
    }
  }

  /**
   * Returns available off-canvas menus.
   */
  public function getOffCanvasSkins() {
    return [
      'bottomsheet',
      'pushdown',
      'scalein',
      'slidein',
      'slidein-oldies',
      'slideover',
      'zoomin',
    ];
  }

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild() {
    if (!isset($this->libraries)) {
      $common = ['version' => '1.x'];
      $libraries = [];
      foreach ($this->loadMultiple() as $key => $skin) {
        $skin_css_path = $this->getPath($key);
        $skin_basename = $this->getName($skin_css_path);

        $libraries['skin.' . $skin_basename] = [
          'css' => [
            'theme' => [
              $skin_css_path => [],
            ],
          ],
        ];
      }

      foreach ($this->getOffCanvasSkins() as $skin) {
        $libraries['offcanvas.' . $skin] = [
          'css' => [
            'theme' => [
              'css/components/ultimenu.offcanvas.' . $skin . '.css' => [],
            ],
          ],
        ];
      }

      foreach ($libraries as &$library) {
        $library += $common;
        $library['dependencies'][] = 'ultimenu/offcanvas';
      }

      $this->libraries = $libraries;
    }
    return $this->libraries;
  }

}
