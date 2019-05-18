<?php

namespace Drupal\cmlexchange\Service;

use Drupal\file\Entity\File;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * FileService.
 */
class FileService implements FileServiceInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Debug Service.
   *
   * @var \Drupal\cmlexchange\Service\DebugServiceInterface
   */
  protected $debugService;

  /**
   * The EntityManager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The c.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new CheckAuth object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\cmlexchange\Service\DebugServiceInterface $debug
   *   The debug service.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   Entity Manager service.
   * @param \Drupal\Core\Entity\FileSystemInterface $file_system
   *   File System service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    DebugServiceInterface $debug,
    EntityManager $entity_manager,
    FileSystemInterface $file_system
  ) {
    $this->configFactory = $config_factory;
    $this->debugService = $debug;
    $this->entityManager = $entity_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * Save file (init function).
   */
  public function file($content, $filename, $cid, $type) {
    $this->cml_id = $cid;
    $this->cml = $this->entityManager->getStorage('cml')->load($cid);
    $this->type = $type;
    $this->content = $content;
    if (strpos($filename, '.xml')) {
      // 'import.xml', 'offers.xml'.
      $file = $this->saveXml($filename);
    }
    elseif (strpos($filename, '.zip')) {
      $file = $this->saveZip($filename);
    }
    else {
      $file = $this->saveImage($filename);
    }
    return $file;
  }

  /**
   * Save XML.
   */
  public function saveXml($filename) {
    $cml = $this->cml;
    $filepath = $this->cmlDir($cml);
    $file = $this->saveFile($filepath, $filename);
    if ($file->id()) {
      $file->display = 1;
      $xmlfiles = $cml->field_file->getValue();
      $xmlfiles[] = ['target_id' => $file->id()];
      $cml->field_file->setValue($xmlfiles);
      $cml->save();
    }
    else {
      $file = FALSE;
    }
    return $file;
  }

  /**
   * Save Image.
   */
  public function saveImage($filename) {
    $path = explode('/', $filename);
    $filename = array_pop($path);
    $filepath = implode('/', $path);
    $file = $this->saveFile($filepath, $filename);
    if (!$file->id()) {
      $file = FALSE;
    }
    return $file;
  }

  /**
   * Save Zip.
   */
  public function saveZip($filename) {
    $cml = $this->cml;
    $filepath = $this->cmlDir($cml);
    $config = $this->configFactory->get('cmlexchange.settings');
    $dir = 'cml-files';
    if ($config->get('file-path')) {
      $dir = $config->get('file-path');
    }
    $filepath = "public://{$dir}/{$filepath}/";
    // Fist time save.
    if ($cml->getState() != 'zip') {
      file_prepare_directory($filepath, FILE_CREATE_DIRECTORY);
      $uri = file_unmanaged_save_data($this->content, "{$filepath}{$filename}", FILE_EXISTS_REPLACE);
      $file = File::create([
        'uri' => $uri,
        'uid' => \Drupal::currentUser()->id(),
        'filename' => $filename,
      ]);
      $file->save();
      if ($file->id()) {
        $xmlfiles = $cml->field_file->getValue();
        $xmlfiles[] = ['target_id' => $file->id()];
        $cml->field_file->setValue($xmlfiles);
      }
      $cml->setState('zip');
      $cml->save();
    }
    // APPEND.
    else {
      $path = $this->fileSystem->realpath("{$filepath}{$filename}");
      file_put_contents($path, $this->content, FILE_APPEND);
    }
    return TRUE;
  }

  /**
   * Save File.
   */
  public function saveFile($filepath, $filename) {
    $config = $this->configFactory->get('cmlexchange.settings');
    $dir = 'cml-files';
    if ($config->get('file-path')) {
      $dir = $config->get('file-path');
    }
    $filepath = "public://{$dir}/{$filepath}/";
    file_prepare_directory($filepath, FILE_CREATE_DIRECTORY);
    $uri = file_unmanaged_save_data($this->content, "{$filepath}{$filename}", FILE_EXISTS_REPLACE);
    $file = File::create([
      'uri' => $uri,
      'uid' => \Drupal::currentUser()->id(),
      'filename' => $filename,
      'status' => 1,
    ]);
    $existing_files = entity_load_multiple_by_properties('file', ['uri' => $uri]);
    if (count($existing_files)) {
      $existing = reset($existing_files);
      $file->fid = $existing->id();
      $file->setOriginalId($existing->id());
    }
    $file->save();
    return $file;
  }

  /**
   * Get cml_id dir.
   */
  public function cmlDir($cml) {
    $type = $cml->type->value;
    $time = format_date($cml->created->value, 'custom', 'Y-m-d--H-i-s');
    $key = substr($cml->uuid->value, 0, 8);
    $cid = $cml->id();
    $dir = "{$type}/{$time}-$key-{$cid}";
    return $dir;
  }

}
