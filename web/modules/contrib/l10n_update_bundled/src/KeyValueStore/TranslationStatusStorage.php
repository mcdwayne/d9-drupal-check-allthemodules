<?php

namespace Drupal\l10n_update_bundled\KeyValueStore;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\KeyValueStore\StorageBase;

/**
 * Defines a key/value store implementation for translation updates.
 */
class TranslationStatusStorage extends StorageBase {

  /**
   * The actual storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $storage;

  /**
   * The resuest time.
   *
   * @var int
   */
  protected $request_time;

  /**
   * Creates an instance of this storage.
   *
   * @param string $collection
   *   The collection name.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $storage
   *   The actual storage.
   * @param int $time
   *   The request time.
   */
  public function __construct($collection, KeyValueStoreInterface $storage, $request_time) {
    parent::__construct($collection);

    $this->storage = $storage;
    $this->request_time = $request_time;
  }

  /**
   * {@inheritdoc}
   */
  public function has($key) {
    return $this->storage->has($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(array $keys) {
    return $this->storage->getMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return $this->storage->getAll();
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    foreach ($value as $translation) {
      if (isset($translation->files['local'])) {
        $file = $translation->files['local'];
        $path = drupal_get_path($translation->project_type, $translation->name) . '/translations/' . $file->langcode . '.po';

        if ($file->uri === $path && file_exists($path)) {
          $timestamp = $this->getPoModificationTime($path);

          if ($timestamp) {
            $file->timestamp = $timestamp;
            $translation->timestamp = $timestamp;
          }
        }
      }
    }

    $this->storage->set($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setIfNotExists($key, $value) {
    if (!$this->has($key)) {
      $this->set($key, $value);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($key, $new_key) {
    $this->storage->rename($key, $new_key);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $keys) {
    $this->storage->deleteMultiple($keys);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $this->storage->deleteAll();
  }

  /**
   * Get the timestamp when a po file was last modified.
   *
   * @param $path
   *   Full path to the po file.
   *
   * @return int|FALSE
   *   UNIX timestamp the file was last modified or false if it could not be determined.
   */
  protected function getPoModificationTime($path) {
    if ($fp = fopen($path, 'rb')) {
      $modified = array();

      // Read the po file headers to find the modification date.
      while ($line = fgets($fp, 10 * 1024)) {
        // Ignore comments.
        if (strncmp('#', $line, 1)) {
          if ($line === '') {
            // Stop searching when all headers were tested.
            break;
          }

          if (preg_match('#^"(POT?\-(?:Creation|Revision)\-Date): (\d{4}-\d{2}-\d{2} \d{2}:\d{2}(?:\+\d{4}))?\\\n"#', $line, $matches)) {
            if (isset($matches[2])) {
              // Parse the found date to a timestamp and keep the most one.
              $modified[] = strtotime($matches[2], $this->request_time);
            }

            if (($modified && $matches[1] === 'PO-Revision-Date') || count($modified) === 2) {
              // Leave as soon as we found what we're looking for.
              break;
            }
          }
        }
      }

      // Close the file pointer.
      fclose($fp);

      // Return the highest modification time.
      if ($modified) {
        return max($modified);
      }
    }

    return FALSE;
  }

}
