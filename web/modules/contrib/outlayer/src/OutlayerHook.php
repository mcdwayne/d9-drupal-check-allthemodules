<?php

namespace Drupal\outlayer;

use Drupal\Core\File\FileSystem;
use Drupal\Core\Template\Attribute;
use Drupal\blazy\Blazy;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Outlayer utility methods for Drupal hooks.
 */
class OutlayerHook {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The outlayer manager service.
   *
   * @var \Drupal\outlayer\OutlayerManagerInterface
   */
  protected $manager;

  /**
   * The library info definition.
   *
   * @var array
   */
  protected $libraryInfoBuild;

  /**
   * Constructs a Outlayer object.
   *
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   * @param \Drupal\outlayer\OutlayerManagerInterface $manager
   *   The outlayer manager service.
   */
  public function __construct(FileSystem $file_system, OutlayerManagerInterface $manager) {
    $this->fileSystem = $file_system;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('file_system'), $container->get('outlayer.manager'));
  }

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild() {
    if (!isset($this->libraryInfoBuild)) {
      $libraries = [];
      foreach (OutlayerDefault::extraLayouts() as $id) {
        $library = 'libraries/isotope-' . $id;
        $filename = $id == 'packery' ? $id . '-mode.pkgd' : $id;
        $ext = is_file($library . '/' . $filename . '.min.js') ? 'min.js' : 'js';

        $libraries['isotope-' . $id]['js']['/' . $library . '/' . $filename . '.' . $ext] = ['weight' => -2];
        $libraries['isotope-' . $id]['dependencies'][] = 'outlayer/isotope';
      }
      $this->libraryInfoBuild = $libraries;
    }

    return $this->libraryInfoBuild;
  }

  /**
   * Implements hook_library_info_alter().
   */
  public function libraryInfoAlter(&$libraries, $extension) {
    if ($extension === 'outlayer' && function_exists('libraries_get_path')) {
      // The main library.
      // @todo remove checks once the library has minified versions.
      if ($library = libraries_get_path('outlayer')) {
        foreach (['item', 'outlayer'] as $name) {
          $ext = is_file($library . '/' . $name . '.min.js') ? 'min.js' : 'js';
          $libraries['outlayer']['js']['/' . $library . '/' . $name . '.' . $ext] = ['weight' => -6];
        }
      }

      if ($library = libraries_get_path('imagesloaded')) {
        $libraries['imagesloaded']['js']['/' . $library . '/imagesloaded.pkgd.min.js'] = ['weight' => -6];
      }

      // Layouts based on outlayer library.
      foreach (['isotope', 'masonry', 'packery'] as $id) {
        // Composer based libraries suffixed with `-layout`.
        $library = libraries_get_path($id) ?: libraries_get_path($id . '-layout');
        if ($library) {
          foreach ($libraries[$id]['js'] as $uri => $info) {
            $basename = $this->fileSystem->basename($uri);
            // @todo when we do not use .pkgd.min.js, extract from sources:
            // @todo $library = $id == 'masonry' ? $library : $library . '/js';
            // @todo $library = strpos($uri, 'layout-modes') !== FALSE ? $library . '/layout-modes' : $library;
            // @todo $libraries[$id]['js']['/' . $library . '/' . $basename] = $info;
            $libraries[$id]['js']['/' . $library . '/dist/' . $basename] = $info;
          }
        }
      }

      // Isotope extra layouts prefixed with `isotope-`.
      foreach (OutlayerDefault::extraLayouts() as $id) {
        $filename = $id == 'packery' ? $id . '-mode.pkgd' : $id;

        if ($library = libraries_get_path('isotope-' . $id)) {
          $ext = is_file($library . '/' . $filename . '.min.js') ? 'min.js' : 'js';
          $libraries['isotope-' . $id]['js']['/' . $library . '/' . $filename . '.' . $ext] = ['weight' => -2];
        }
      }
    }
  }

  /**
   * Implements hook_config_schema_info_alter().
   */
  public function configSchemaInfoAlter(array &$definitions) {
    if (isset($definitions['outlayer_base'])) {
      Blazy::configSchemaInfoAlter($definitions, 'outlayer_base', OutlayerDefault::extendedSettings());
    }
  }

  /**
   * Returns common button markup.
   *
   * @todo use a themeable output.
   */
  public static function button(array $variables) {
    $title      = $variables['title'];
    $classes    = ['btn', 'button', 'button--outlayer'];
    $attributes = new Attribute();

    if (!empty($variables['filter'])) {
      $attributes->setAttribute('data-filter', $variables['filter']);
      $classes[] = 'btn-primary';
    }
    if (!empty($variables['sorter'])) {
      $attributes->setAttribute('data-sort-by', $variables['sorter']);
      $classes[] = 'btn-secondary';
    }

    $classes = array_merge($classes, $variables['classes']);
    $attributes->addClass($classes);
    $attributes->setAttribute('type', 'button');

    return [
      '#markup' => '<button' . $attributes . '>' . $title . '</button>',
      '#allowed_tags' => ['button', 'span'],
    ];
  }

}
