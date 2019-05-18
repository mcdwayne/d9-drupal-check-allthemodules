<?php

namespace Drupal\flipping_book;
use Drupal\file\Entity\File;
use Drupal\flipping_book\Entity\FlippingBook as FlippingBookEntity;

/**
 * Interface FlippingBookInterface.
 *
 * @package Drupal\flipping_book
 */
interface FlippingBookInterface {

  /**
   * Private Flipping Book stream definition.
   */
  const FLIPPING_BOOK_PRIVATE = 'private://flipping_books';

  /**
   * Public Flipping Book stream definition.
   */
  const FLIPPING_BOOK_PUBLIC = 'public://flipping_books';

  /**
   * Delete Flipping Book Archive.
   *
   * @param \Drupal\flipping_book\Entity\FlippingBook $flippingBook
   *   A Flipping Book entity.
   */
  public function deleteArchive(FlippingBookEntity $flippingBook);

  /**
   * Extract upload location.
   *
   * @param \Drupal\flipping_book\Entity\FlippingBook $flippingBook
   *   A Flipping Book entity.
   *
   * @return string
   *   The Flipping Book upload location.
   */
  public function extractUploadLocation(FlippingBookEntity $flippingBook);

  /**
   * Get Bundle Entity.
   *
   * @param \Drupal\flipping_book\Entity\FlippingBook $flippingBook
   *   A Flipping Book entity.
   *
   * @return \Drupal\flipping_book\Entity\FlippingBookType
   *   The Flipping Book type entity.
   */
  public function getBundleEntity(FlippingBookEntity $flippingBook);

  /**
   * Build Flipping Book URL.
   *
   * @param \Drupal\flipping_book\Entity\FlippingBook $flippingBook
   *   A Flipping Book entity.
   *
   * @return string
   *   The complete Flipping Book URL used for visualization.
   */
  public function buildFlippingBookUrl(FlippingBookEntity $flippingBook);

  /**
   * Extract uploaded archive.
   *
   * @param string $file_path
   *   The archive source path.
   * @param string $destination
   *   The destination path.
   *
   * @throws \Exception
   */
  public function extractArchive($file_path, $destination);

  /**
   * Helper function to sanitize flipping_book filename.
   *
   * @param string $filename.
   *   A string representing the file name.
   */
  public function cleanFilename($filename);

  /**
   * Prepare export directory.
   *
   * @param \Drupal\file\Entity\File $file
   *   A File entity.
   * @param string $export_location
   *   The export location path.
   *
   * @return array
   *   An array with filepath and destination info.
   */
  public function prepareExportDirectory(File $file, $export_location);

}
