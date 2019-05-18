<?php

namespace Drupal\library_select\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\library_select\LibraryFileStorage;

/**
 * Defines the Library Select entity.
 *
 * @ConfigEntityType(
 *   id = "library_select_entity",
 *   label = @Translation("Library Select"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\library_select\LibrarySelectEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\library_select\Form\LibrarySelectEntityForm",
 *       "edit" = "Drupal\library_select\Form\LibrarySelectEntityForm",
 *       "delete" = "Drupal\library_select\Form\LibrarySelectEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\library_select\LibrarySelectEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "library_select_entity",
 *   admin_permission = "administer library select",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/library_select_entity/{library_select_entity}",
 *     "add-form" = "/admin/config/development/library_select_entity/add",
 *     "edit-form" = "/admin/config/development/library_select_entity/{library_select_entity}/edit",
 *     "delete-form" = "/admin/config/development/library_select_entity/{library_select_entity}/delete",
 *     "collection" = "/admin/config/development/library_select_entity"
 *   }
 * )
 */
class LibrarySelectEntity extends ConfigEntityBase implements LibrarySelectEntityInterface {

  /**
   * The Library Select ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Library Select label.
   *
   * @var string
   */
  protected $label;


  /**
   * The custom code of the css.
   *
   * @var string
   */
  public $css_code;

  /**
   * The custom code of the js.
   *
   * @var string
   */
  public $js_code;


  /**
   * The list of the css.
   *
   * @var string
   */
  public $css_files;

  /**
   * The list of the js.
   *
   * @var string
   */
  public $js_files;

  /**
   * Allow pre process css, js.
   *
   * @var bool
   */
  public $preprocess = FALSE;

  /**
   * Css extension.
   *
   * @var string
   */
  public $cssExtension = 'css';

  /**
   * JS extension.
   *
   * @var string
   */
  public $jsExtension = 'js';

  /**
   * Get css files.
   *
   * @return string
   *   The list of the css files.
   */
  public function getCssFiles() {
    return $this->css_files;
  }

  /**
   * Get css files.
   *
   * @return array
   *   The list of the css files.
   */
  public function getCssFilesArray() {
    return explode("\r\n", $this->getCssFiles());
  }

  /**
   * Get js files.
   *
   * @return array
   *   The list of the css files.
   */
  public function getJsFilesArray() {
    return explode("\r\n", $this->getJsFiles());
  }

  /**
   * Get js files.
   *
   * @return string
   *   The list of the js files.
   */
  public function getJsFiles() {
    return $this->js_files;
  }

  /**
   * Get css code.
   *
   * @return string
   *   The code.
   */
  public function getCssCode() {
    return $this->css_code;
  }

  /**
   * Get js code.
   *
   * @return string
   *   The code.
   */
  public function getJsCode() {
    return $this->js_code;
  }

  /**
   * Get custom code.
   *
   * @param string $type
   *   The type.
   *
   * @return string
   *   The code.
   */
  public function getCode($type) {
    if ($type === 'css') {
      return $this->getCssCode();
    }

    if ($type === 'js') {
      return $this->getJsCode();
    }
  }

  /**
   * Gets the library array used in library_info_build.
   *
   * @return array
   *   Library info array for this asset.
   */
  public function libraryInfo() {
    $library_info = [];

    foreach ($this->getJsFilesArray() as $path) {
      $path = trim($path);
      if (!empty($path)) {
        $library_info['js'][$path] = ['preprocess' => $this->preprocess];
      }
    }

    if (!empty($this->getJsCode())) {
      $path = $this->filePathRelativeToDrupalRoot($this->jsExtension);
      $library_info['js'][$path] = ['preprocess' => $this->preprocess];
    }

    foreach ($this->getCssFilesArray() as $path) {
      if (!empty($path)) {
        $library_info['css']['theme'][$path] = [
          'weight' => 0,
          'media' => 'all',
          'preprocess' => strpos($path, '//') === FALSE,
        ];
      }
    }

    if (!empty($this->getCssCode())) {
      $path = $this->filePathRelativeToDrupalRoot($this->cssExtension);
      $library_info['css']['theme'][$path] = [
        'weight' => 0,
        'media' => 'all',
        'preprocess' => TRUE,
      ];
    }

    return $library_info;
  }

  /**
   * Get internal file uri.
   *
   * @param string $type
   *   The type css or js.
   *
   * @return string
   *   The file uri.
   */
  public function internalFileUri($type) {
    $storage = new LibraryFileStorage($this);
    return $storage->createFile($type);
  }

  /**
   * Get file path relative to drupal root to use in library info.
   *
   * @param string $type
   *   The file type, css or js.
   *
   * @return string
   *   File path relative to drupal root, with leading slash.
   */
  protected function filePathRelativeToDrupalRoot($type) {
    // @todo See if we can simplify this via file_url_transform_relative().
    $path = parse_url(file_create_url($this->internalFileUri($type)), PHP_URL_PATH);
    $path = str_replace(base_path(), '/', $path);
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $original_id = $this->getOriginalId();
    if ($original_id) {
      $original = $storage->loadUnchanged($original_id);
      // This happens to fail on config import.
      if ($original instanceof LibrarySelectEntityInterface) {
        $asset_file_storage = new LibraryFileStorage($original);
        $asset_file_storage->deleteFiles();
      }
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    foreach ($entities as $entity) {
      /** @var \Drupal\library_select\Entity\LibrarySelectEntity $entity */
      $original_id = $entity->getOriginalId();
      if ($original_id) {
        $original = $storage->loadUnchanged($original_id);
        // This happens to fail on config import.
        if ($original instanceof LibrarySelectEntityInterface) {
          $asset_file_storage = new LibraryFileStorage($original);
          $asset_file_storage->deleteFiles();
        }
      }
    }
    parent::preDelete($storage, $entities);
  }

}
