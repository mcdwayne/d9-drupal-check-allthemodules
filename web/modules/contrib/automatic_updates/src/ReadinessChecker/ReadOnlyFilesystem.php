<?php

namespace Drupal\automatic_updates\ReadinessChecker;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use DrupalFinder\DrupalFinder;
use Psr\Log\LoggerInterface;

/**
 * Read only filesystem checker.
 */
class ReadOnlyFilesystem extends Filesystem {
  use StringTranslationTrait;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * ReadOnlyFilesystem constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \DrupalFinder\DrupalFinder $drupal_finder
   *   The Drupal finder.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(LoggerInterface $logger, DrupalFinder $drupal_finder, FileSystemInterface $file_system) {
    $this->logger = $logger;
    $this->drupalFinder = $drupal_finder;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCheck() {
    return $this->readOnlyCheck();
  }

  /**
   * Check if the filesystem is read only.
   */
  protected function readOnlyCheck() {
    $messages = [];
    if ($this->areSameLogicalDisk($this->getRootPath(), $this->getVendorPath())) {
      $error = $this->t('Logical disk at "@path" is read only. Updates to Drupal cannot be applied against a read only file system.', ['@path' => $this->rootPath]);
      $this->doReadOnlyCheck($this->getRootPath(), 'core/core.api.php', $messages, $error);
    }
    else {
      $error = $this->t('Drupal core filesystem at "@path" is read only. Updates to Drupal core cannot be applied against a read only file system.', ['@path' => $this->rootPath . '/core']);
      $this->doReadOnlyCheck($this->getRootPath(), 'core.api.php', $messages, $error);
      $error = $this->t('Vendor filesystem at "@path" is read only. Updates to the site\'s code base cannot be applied against a read only file system.', ['@path' => $this->vendorPath]);
      $this->doReadOnlyCheck($this->getVendorPath(), 'composer/LICENSE', $messages, $error);
    }
    return $messages;
  }

  /**
   * Do the read only check.
   *
   * @param string $file_path
   *   The filesystem to test.
   * @param string $file
   *   The file path.
   * @param array $messages
   *   The messages array of translatable strings.
   * @param \Drupal\Component\Render\MarkupInterface $message
   *   The error message to add if the file is read only.
   */
  protected function doReadOnlyCheck($file_path, $file, array &$messages, MarkupInterface $message) {
    try {
      // If we can copy and delete a file, then we don't have a read only
      // file system.
      if ($this->fileSystem->copy("$file_path/$file", "$file_path/$file.automatic_updates", FileSystemInterface::EXISTS_REPLACE)) {
        // Delete it after copying.
        $this->fileSystem->delete("$file_path/$file.automatic_updates");
      }
      else {
        $this->logger->error($message);
        $messages[] = $message;
      }
    }
    catch (FileException $exception) {
      $messages[] = $message;
    }
  }

}
