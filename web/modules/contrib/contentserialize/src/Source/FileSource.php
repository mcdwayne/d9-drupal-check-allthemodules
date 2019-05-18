<?php

namespace Drupal\contentserialize\Source;

use Drupal\Component\Uuid\Uuid;
use Drupal\contentserialize\SerializedEntity;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Loads serialized entities and metadata from the filesystem.
 */
class FileSource implements \IteratorAggregate, SourceInterface {

  /**
   * The path of the folder to load from.
   *
   * @var string
   */
  protected $path;

  /**
   * Creates a new FileSource.
   *
   * @param string $path
   *   The path of the folder to load from.
   */
  public function __construct($path) {
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return $this->loadAll();
  }

  /**
   * Load and yield every serialised entity.
   *
   * @return \Generator|\Drupal\contentserialize\SerializedEntity[]
   *   Serialized entities keyed by UUID.
   *
   * @throws \RuntimeException
   *   If an exported entity file can't be read or has been deleted.
   */
  protected function loadAll() {
    foreach ($this->getItemsMetadata() as $uuid => $item) {
      $filename = "$this->path/$item[filename]";
      $serialized = file_get_contents($filename);
      // The file's been deleted or access is denied. Either way it's probably
      // not expected behaviour.
      if ($serialized === FALSE) {
        throw new \RuntimeException("Can't read from '$filename'");
      }
      yield $uuid => new SerializedEntity(
        $serialized,
        $item['format'],
        $uuid,
        $item['entity_type_id']
      );
    }
  }

  /**
   * Get the metadata for all the serialized entities in import order.
   *
   * At the moment the order is arbitrary.
   *
   * @return array
   */
  protected function getItemsMetadata() {
    $metadata = [];
    $uuid_pattern = Uuid::VALID_PATTERN;
    // Use named capture groups because we're injecting a pattern.
    $file_pattern = "/^(?<uuid>$uuid_pattern)\.(?<entity_type_id>[^.]+)\.(?<format>[^.]+)$/";
    if (!is_dir($this->path)) {
      throw new InvalidArgumentException("$this->path doesn't exist or isn't readable");
    }
    $files = scandir($this->path);
    if ($files === FALSE) {
      throw new InvalidArgumentException("Can't read from $this->path");
    }
    foreach ($files as $filename) {
      $matches = [];
      if (preg_match($file_pattern, $filename, $matches)) {
        $metadata[$matches['uuid']] = [
          'filename' => $filename,
          'entity_type_id' => $matches['entity_type_id'],
          'format' => $matches['format'],
        ];
      }
    }

    return $metadata;
  }

}
