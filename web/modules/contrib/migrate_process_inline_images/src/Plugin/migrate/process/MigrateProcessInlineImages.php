<?php

namespace Drupal\migrate_process_inline_images\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Image;

/**
 * Provides a 'MigrateProcessInlineImages' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "inline_images"
 * )
 */
class MigrateProcessInlineImages extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Image location base.
   *
   * @var string
   */
  protected $imageBase;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Stream Wrapper Manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('file_system'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager, Client $httpClient, FileSystemInterface $fileSystem, StreamWrapperManagerInterface $streamWrapperManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->fileStorage = $this->entityTypeManager->getStorage('file');
    $this->httpClient = $httpClient;
    $this->fileSystem = $fileSystem;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->imageBase = isset($this->configuration['base'])
      ? $this->configuration['base']
      : 'public://migrate_images';
    if (!$this->fileSystem->uriScheme($this->imageBase)) {
      throw new MigrateException('Base path specification must be in URI form, e.g., public://');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $domCrawler = new Crawler($value, NULL, Url::fromRoute('<front>')->setAbsolute()->toString());
    // Search for all <img> tag in the value (usually the body).
    if ($images = $domCrawler->filter('img')->images()) {
      foreach ($images as $image) {
        // Clean up the attributes in the img tag.
        $this->cleanUpImageAttributes($image, $migrate_executable);
      }
      return $domCrawler->html();
    }

    return $value;
  }

  /**
   * Resolve an existing local file with the same path.
   *
   * @param string $imagePath
   *   The non-external image path from the image tag.
   *
   * @return \Drupal\file\FileInterface
   *   The found file, or null if we couldn't find it.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Exception when file not found.
   */
  protected function findLocalFile($imagePath) {
    // If the image is stored in 'public://image', then an img src of
    // 'files/image/subdir/pict.jpg' will correspond to an entity uri
    // of 'subdir/pict.jpg'. The 'base' in this example is 'files/image';
    // strip the base off the img src so that we can use it to search for
    // the file by its entity uri.
    $imageBase = rtrim($this->imageBase, '/');
    $imagePath = str_replace('/' . $imageBase . '/', '', $imagePath);

    $fids = $this->fileStorage->getQuery()
      ->condition('uri', '%' . $imagePath . '%', 'LIKE')
      ->range(0, 1)
      ->execute();

    if ($fids) {
      return $this->fileStorage->load(reset($files));
    }
    throw new MigrateException('Could not find existing file.');
  }

  /**
   * Download an external file.
   *
   * @see \Drupal\migrate\Plugin\migrate\process\Download
   *
   * @param string $imagePath
   *   The external image path.
   *
   * @return \Drupal\file\FileInterface
   *   The downloaded file.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Exception on download.
   */
  protected function downloadFile($imagePath) {
    $destination = $this->imageBase
      . DIRECTORY_SEPARATOR
      . $this->fileSystem->basename($imagePath);
    // Modify the destination filename if necessary.
    $replace = !empty($this->configuration['rename']) ?
      FILE_EXISTS_RENAME :
      FILE_EXISTS_REPLACE;
    $final_destination = file_destination($destination, $replace);

    // Try opening the file first, to avoid calling file_prepare_directory()
    // unnecessarily. We're suppressing fopen() errors because we want to try
    // to prepare the directory before we give up and fail.
    $destination_stream = @fopen($final_destination, 'w');
    if (!$destination_stream) {
      // If fopen didn't work, make sure there's a writable directory in place.
      $dir = $this->fileSystem->dirname($final_destination);
      if (!file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        throw new MigrateException("Could not create or write to directory '$dir'");
      }
      // Let's try that fopen again.
      $destination_stream = @fopen($final_destination, 'w');
      if (!$destination_stream) {
        throw new MigrateException("Could not write to file '$final_destination'");
      }
    }

    // Stream the request body directly to the final destination stream.
    $this->configuration['guzzle_options']['sink'] = $destination_stream;

    try {
      // Make the request. Guzzle throws an exception for anything but 200.
      $this->httpClient->get($imagePath, $this->configuration['guzzle_options']);
    }
    catch (\Exception $e) {
      throw new MigrateException("{$e->getMessage()} ({$imagePath})");
    }

    $file = File::create([
      'uri' => $final_destination,
    ]);
    $file->save();
    return $file;
  }

  /**
   * Process the image tag by either locating a local file or downloading it.
   *
   * @param \Symfony\Component\DomCrawler\Image $image
   * @param \Drupal\migrate\MigrateExecutableInterface $migrateExecutable
   *
   * @return void
   */
  protected function cleanUpImageAttributes(Image $image, MigrateExecutableInterface $migrateExecutable) {
    $imagePath = $image->getUri();
    try {
      if (Url::fromUri($imagePath)->isExternal()) {
        $file = $this->downloadFile($imagePath);
      }
      else {
        $file = $this->findLocalFile($imagePath);
      }
    }
    catch (MigrateException $e) {
      $migrateExecutable
        ->saveMessage(
          "Could not process image path {$imagePath}: {$e->getMessage()}",
          MigrationInterface::MESSAGE_ERROR
        );
      // Do no manipulation.
      return;
    }

    $this->determineAlign($image);
    $streamWrapper = $this->streamWrapperManager->getViaUri($file->getFileUri());
    if ($streamWrapper instanceof \Drupal\Core\StreamWrapper\LocalStream) {
      $url = file_url_transform_relative(file_create_url($file->getFileUri()));
    }
    else {
      $url = $streamWrapper->getExternalUrl();
    }
    // Attempt to get a local path if it's supported; otherwise, external.
    $image->getNode()->setAttribute('data-entity-uuid', $file->uuid());
    $image->getNode()->setAttribute('data-entity-type', 'file');
    $image->getNode()->setAttribute('src', $url);
  }

  /**
   * Add data-align attribute to match existing alignment.
   *
   * This is in its own method to allow easy overriding, e.g. to include
   * additional logic to match legacy tags, e.g. those added by
   * wysiwyg_imageupload module.
   *
   * @param \Symfony\Component\DomCrawler\Image $image
   */
  protected function determineAlign(Image $image)
  {
    if ($alignment = $image->getNode()->getAttribute('align')) {
      $image->getNode()->setAttribute('data-align', $alignment);
    }
  }
}
