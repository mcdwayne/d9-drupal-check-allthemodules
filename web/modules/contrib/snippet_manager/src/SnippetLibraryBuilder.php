<?php

namespace Drupal\snippet_manager;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides snippet library builder.
 */
class SnippetLibraryBuilder {

  use StringTranslationTrait;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Snippet renderer.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * File system wrapper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs a new SnippetLibraryBuilder instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system wrapper.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelInterface $logger, FileSystemInterface $file_system, LibraryDiscoveryInterface $library_discovery) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->fileSystem = $file_system;
    $this->libraryDiscovery = $library_discovery;
  }

  /**
   * Builds snippet libraries.
   */
  public function build() {
    $libraries = [];

    $storage = $this->entityTypeManager->getStorage('snippet');

    /** @var \Drupal\snippet_manager\SnippetInterface $snippet */
    foreach ($storage->loadMultiple() as $snippet) {
      $name = 'snippet_' . $snippet->id();
      // Preprocess option was added in 8.x-1.4, so it might not be defined
      // explicitly.
      $css = $snippet->get('css');
      if ($css['status']) {
        $libraries[$name]['css'][$css['group']]['/' . $this->getFilePath('css', $snippet)] = [
          'preprocess' => !empty($css['preprocess']),
        ];
      }
      $js = $snippet->get('js');
      if ($js['status']) {
        $libraries[$name]['js']['/' . $this->getFilePath('js', $snippet)] = [
          'preprocess' => !empty($js['preprocess']),
        ];
      }
    }

    return $libraries;
  }

  /**
   * Updates library assets.
   */
  public function updateAssets(SnippetInterface $snippet, SnippetInterface $original_snippet = NULL) {

    $clear_cache = FALSE;

    foreach (['css', 'js'] as $type) {
      $file_path = DRUPAL_ROOT . '/' . $this->getFilePath($type, $snippet);
      $data = $snippet->get($type);
      $original_data = $original_snippet ? $original_snippet->get($type) : NULL;
      if (!$data['status']) {
        // Check if the file exists to avoid unwanted log notices.
        file_exists($file_path) && file_unmanaged_delete($file_path);
      }
      elseif (!$original_snippet || $data != $original_data) {
        $this->writeData($file_path, $data['value']);
      }

      if ($data != $original_data) {
        $clear_cache = TRUE;
      }
    }

    // Clear library caches if something besides the code has been changed.
    $clear_cache && $this->libraryDiscovery->clearCachedDefinitions();
  }

  /**
   * Saves library data to a given location.
   *
   * @return bool
   *   TRUE if the was successfully created and is writable or FALSE on error.
   */
  protected function writeData($file_path, $data) {

    $directory = $this->fileSystem->dirname($file_path);

    if (file_prepare_directory($directory, FILE_CREATE_DIRECTORY)) {
      if (file_unmanaged_save_data($data, $file_path, FILE_EXISTS_REPLACE)) {
        return TRUE;
      }
    }

    $message = $this->t('Could not create file %file', ['%file' => $file_path]);
    drupal_set_message($message, 'error');
    $this->logger->error($message);

    return FALSE;
  }

  /**
   * Returns a path to snippet asset file.
   *
   * @param string $type
   *   File type: css or js.
   * @param SnippetInterface $snippet
   *   The snippet.
   *
   * @return string
   *   Path to the file.
   */
  public function getFilePath($type, SnippetInterface $snippet) {
    return PublicStream::basePath() . '/snippet/' . Crypt::hashBase64($snippet->id()) . '.' . $type;
  }

}
