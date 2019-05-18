<?php

namespace Drupal\flipping_book;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Archiver\ArchiverManager;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\flipping_book\Entity\FlippingBook as FlippingBookEntity;

/**
 * Class FlippingBook.
 *
 * @package Drupal\flipping_book
 */
class FlippingBook implements FlippingBookInterface {

  /**
   * File System service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Archiver Manager service.
   *
   * @var \Drupal\Core\Archiver\ArchiverManager
   */
  protected $pluginManagerArchiver;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * FlippingBook constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   A File System service instance.
   * @param \Drupal\Core\Archiver\ArchiverManager $plugin_manager_archiver
   *   An Archiver Manager service instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service instance.
   */
  public function __construct(FileSystemInterface $file_system, ArchiverManager $plugin_manager_archiver, EntityTypeManagerInterface $entity_type_manager) {
    $this->fileSystem = $file_system;
    $this->pluginManagerArchiver = $plugin_manager_archiver;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function extractUploadLocation(FlippingBookEntity $flippingBook) {
    $flipping_book_type = $this->getBundleEntity($flippingBook);
    return $flipping_book_type->get('location');
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleEntity(FlippingBookEntity $flippingBook) {
    $storage = $this->entityTypeManager->getStorage('flipping_book_type');
    return $storage->load($flippingBook->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function prepareExportDirectory(File $file, $export_location) {
    $filepath = $this->fileSystem->realpath($file->getFileUri());
    $dir = pathinfo($this->cleanFilename($file->getFilename()), PATHINFO_FILENAME);

    file_prepare_directory($export_location, FILE_CREATE_DIRECTORY);
    $destination = file_destination($export_location . '/' . $dir, FILE_EXISTS_RENAME);

    return [
      'filepath' => $filepath,
      'destination' => $destination,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteArchive(FlippingBookEntity $flippingBook) {
    $dir = $flippingBook->get('directory')->value;
    if (empty($dir)) {
      return;
    }

    $location = $this->extractUploadLocation($flippingBook);
    file_unmanaged_delete_recursive($this->fileSystem->realpath("$location/$dir"));
  }

  /**
   * {@inheritdoc}
   */
  public function extractArchive($file_path, $destination) {
    $archiver = $this->pluginManagerArchiver->getInstance(['filepath' => $file_path]);
    $archiver->extract($this->fileSystem->realpath($destination . '/'));
  }


  /**
   * {@inheritdoc}
   */
  public function buildFlippingBookUrl(FlippingBookEntity $flippingBook) {
    $location = $this->extractUploadLocation($flippingBook);
    $dir = $flippingBook->getDirectory();
    $uri = file_create_url("$location/$dir/index.html");
    return Url::fromUri($uri);
  }

  /**
   * {@inheritdoc}
   */
  public function cleanFilename($filename) {
    // Replace whitespace.
    $filename = str_replace(' ', '_', $filename);
    // Remove remaining unsafe characters.
    $filename = preg_replace('![^0-9A-Za-z_.-]!', '', $filename);
    // Remove multiple consecutive non-alphabetical characters.
    $filename = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $filename);
    // Force lowercase to prevent issues on case-insensitive file systems.
    $filename = strtolower($filename);

    return $filename;
  }

}
