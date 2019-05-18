<?php

namespace Drupal\micro_theme;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class ManagerAsset.
 */
class MicroManagerAsset implements MicroManagerAssetInterface {

  /**
   * Drupal\Core\File\FileSystemInterface definition.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected  $config;

  /**
   * Drupal\usine_theme\LibrariesServiceInterface definition.
   *
   * @var \Drupal\usine_theme\LibrariesServiceInterface
   */
  protected  $librariesService;

  /**
   * Drupal\Core\Theme\ThemeManagerInterface definition.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected  $themeManager;

  /**
   * Drupal\Core\State\StateInterface definition.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;


  /**
   * ManagerAsset constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\micro_theme\MicroLibrariesServiceInterface $libraries_services
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(FileSystemInterface $file_system, ConfigFactoryInterface $config_factory, MicroLibrariesServiceInterface $libraries_services, ThemeManagerInterface $theme_manager, StateInterface $state) {
    $this->fileSystem = $file_system;
    $this->config = $config_factory;
    $this->librariesService = $libraries_services;
    $this->themeManager = $theme_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function cssInternalFileUri($type, $file_model, $replace_pattern, $site_id) {
    $storage = new MicroCssFileStorage($type, $file_model, $replace_pattern, $site_id);
    return $storage->createFile();
  }

  /**
   * {@inheritdoc}
   */
  public function cssFilePath($type, $file_model, $replace_pattern, $site_id) {
    // @todo See if we can simplify this via file_url_transform_relative().
    $path = parse_url(file_create_url($this->cssInternalFileUri($type, $file_model, $replace_pattern, $site_id)), PHP_URL_PATH);
    $path = str_replace(base_path(), '/', $path);
    return $path;
  }


  /**
   * Get the css font/color file path.
   *
   * @param string $type
   *   The type of asset to get (font or color)
   * @param int $site_id
   *   The micro site id.
   *
   * @return string
   */
  public function getAsset($type, $site_id) {
    $config = $this->state->get('micro_theme:' . $site_id);
    $file_type = 'file_' . $type;
    $replace_pattern = [];
    $override_type = 'override_' . $type;

    if (!$this->hasAssetOverride($type, $site_id)) {
      return '';
    }

    $file_model = !empty($config[$type][$file_type]) ? $config[$type][$file_type] : '';
    if (!is_file($file_model)) {
      return '';
    }

    switch ($type) {
      case 'font':
        $replace_pattern = [
          'BASE_FONT' => $this->librariesService->getFont($config[$type]['base_font']),
          'TITLE_FONT' => $this->librariesService->getFont($config[$type]['title_font']),
        ];
        break;
      case 'color':
        $colors_key = $this->librariesService->getColorsKey(TRUE);
        $replace_pattern = [];
        foreach ($colors_key as $color_key) {
          $replace_pattern[strtoupper($color_key)] = $config[$type]['palette'][$color_key];
        }
        break;
    }

    return $this->cssFilePath($type, $file_model, $replace_pattern, $site_id);
  }

  /**
   * Get the css font file path.
   *
   * @param string $type
   *   The type of asset to get (font or color)
   * @param int $site_id
   *   The micro site id.
   *
   * @return string
   */
  public function hasAssetOverride($type, $site_id) {
    $settings = $this->state->get('micro_theme:' . $site_id);
    $override = 'override_' . $type;
    if (isset($settings[$type][$override]) && $settings[$type][$override]) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get a value from the state settings.
   *
   * @param string $type
   *   The type of asset to get (font or color)
   * @param int $site_id
   *   The micro site id.
   * @param string $key
   *  The key to retrieve.
   *
   * @return mixed
   */
  public function getValue($type, $site_id, $key) {
    $settings = $this->state->get('micro_theme:' . $site_id);
    if (isset($settings[$type][$key]) && !empty($settings[$type][$key])) {
      return $settings[$type][$key];
    }
    return NULL;
  }


  /**
   * @return string
   *   The theme name.
   */
  public function getActiveTheme() {
    return $this->themeManager->getActiveTheme()->getName();
  }

  /**
   * @return bool
   */
  public function isDefaultTheme() {
    $default_theme = $this->config->get('system.theme')->get('default');
    return $default_theme == $this->getActiveTheme();
  }

}
