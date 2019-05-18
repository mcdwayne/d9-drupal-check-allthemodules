<?php

namespace Drupal\rokka;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rokka\Entity\RokkaMetadata;
use Drupal\rokka\RokkaAdapter\StreamWrapper;
use GuzzleHttp\Exception\GuzzleException;
use Rokka\Client\Core\SourceImage;

/**
 *
 */
class RokkaStreamWrapper extends StreamWrapper implements StreamWrapperInterface {

  use StringTranslationTrait;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var RokkaService
   */
  private $rokkaService;

  /**
   * Construct a new stream wrapper.
   *
   * @internal RokkaServiceInterface $rokkaService
   * @internal LoggerInterface $logger
   */
  public function __construct() {
    // Dependency injection will not work here, since stream wrappers
    // are not loaded the normal way: PHP creates them automatically
    // when certain file functions are called.  This prevents us from
    // passing arguments to the constructor, which we'd need to do in
    // order to use standard dependency injection as is typically done
    // in Drupal 8.
    $this->logger = \Drupal::service('rokka.logger');
    $this->rokkaService = \Drupal::service('rokka.service');

    parent::__construct($this->rokkaService->getRokkaImageClient());
  }

  /**
   * Implements getMimeType().
   */
  public static function getMimeType($uri, $mapping = NULL) {
    // *.
    if (!isset($mapping)) {
      // The default file map, defined in file.mimetypes.inc is quite big.
      // We only load it when necessary.
      include_once DRUPAL_ROOT . '/includes/file.mimetypes.inc';
      $mapping = file_mimetype_mapping();
    }

    $extension = '';
    $file_parts = explode('.', basename($uri));

    // Remove the first part: a full filename should not match an extension.
    array_shift($file_parts);

    // Iterate over the file parts, trying to find a match.
    // For my.awesome.image.jpeg, we try:
    // - jpeg
    // - image.jpeg, and
    // - awesome.image.jpeg.
    while ($additional_part = array_pop($file_parts)) {
      $extension = strtolower($additional_part . ($extension ? '.' . $extension : ''));
      if (isset($mapping['extensions'][$extension])) {
        return $mapping['mimetypes'][$mapping['extensions'][$extension]];
      }
    }
    // */.
    return 'application/octet-stream';
  }

  /**
   * {@inheritdoc}
   */
  public static function register() {
    parent::register();
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * Implements getTarget().
   *
   * The "target" is the portion of the URI to the right of the scheme.
   * So in rokka://test.txt, the target is 'example/test.txt'.
   */
  public function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list($scheme, $target) = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    // In the rokka:// scheme, there is never a leading slash on the target.
    return trim($target, '\/');
  }

  /**
   * Implements getDirectoryPath().
   *
   * In this case there is no directory string, so return an empty string.
   */
  public function getDirectoryPath() {
    return '';
  }

  /**
   * Returns a web accessible URL for the resource.
   *
   * This function should return a URL that can be embedded in a web page
   * and accessed from a browser. For example, the external URL of
   * "youtube://xIpLd0WQKCY" might be
   * "http://www.youtube.com/watch?v=xIpLd0WQKCY".
   *
   * @return string|null
   *   Returns a string containing a web accessible URL for the resource.
   */
  public function getExternalUrl() {

    $stack_name = 'dynamic/noop';

    if (strpos($this->uri, 'rokka://styles/') === 0) {
      $exploded_uri = explode('/', $this->uri);
      $stack_name = $exploded_uri[3];
    }

    $meta = $this->doGetMetadataFromUri($this->uri);

    if (!($meta instanceof RokkaMetadata)) {
      $this->logger->critical('Error getting getExternalUrl() for "{uri}": RokkaMetadata not found!', [
        'uri' => $this->uri,
      ]);

      return NULL;
    }

    $defaultStyle = $this->rokkaService->getSettings('source_image_style') ? $this->rokkaService->getSettings('source_image_style') : $stack_name;
    $name = NULL;

    if (!$this->rokkaService->getSettings('use_hash_as_name')) {
      $filename = pathinfo($meta->getUri(), PATHINFO_FILENAME);
      $name = RokkaService::cleanRokkaSeoFilename($filename);
    }

    /** @var \Drupal\rokka\Entity\RokkaStack $stackEntity */
    $stackEntity = $this->rokkaService->loadStackByName($stack_name);
    $outputFormat = $meta->getFormat() ?? 'jpg';
    if (!empty($stackEntity) && $outputFormat !== 'png' ) {
      // Let the rokka stack alter the output image format,
      // except for PNG because else we lose transparencies.
      $outputFormat = $stackEntity->getOutputFormat() ?? 'jpg';
    }
    $externalUri = self::$imageClient->getSourceImageUri($meta->getHash(), $defaultStyle, $outputFormat, $name);
    return (string) $externalUri;
  }

  /**
   * Callback function invoked by the underlying stream when the Rokka HASH is
   * needed instead of the standard URI (examples includes the deletion of an
   * image from Rokka.io or the uri_stat() function).
   *
   * @param $uri
   *
   * @return \Drupal\rokka\Entity\RokkaMetadata|null
   */
  protected function doGetMetadataFromUri($uri) {
    $metadata = $this->rokkaService->loadRokkaMetadataByUri(parent::sanitizeUri($uri));
    if (!empty($metadata)) {

      return reset($metadata);
    }
    if (preg_match('#rokka\:.*rokka_default_image\.jpg#', $uri)) {
      // FIXME... the default image is too large.
      $hash = 'd68ef212f18f8f20130a38f31ca5e945e93446a9';
      // $hash = '2a384f375f91bc6a87a75c979b7387bfc7c93041';.
      $meta = RokkaMetadata::create(['hash' => $hash, 'filesize' => 666]);
      return $meta;
    }
    if (preg_match('#rokka\:.*/default_images/#', $uri)) {
      // FIXME... the default image is too large.
      $hash = 'd68ef212f18f8f20130a38f31ca5e945e93446a9';
      // $hash = '2a384f375f91bc6a87a75c979b7387bfc7c93041';.
      $meta = RokkaMetadata::create(['hash' => $hash, 'filesize' => 666]);
      return $meta;
    }
    return NULL;
  }

  /**
   * Override the unlink() function. Instead of directly deleting the underlying
   * Rokka image, we must check if the same HASH has been assigned to another
   * file: this can happen when the user uploads multiple time the same image.
   * For each uploaded image Drupal will assign it a different FID/URI, but
   * Rokka is referencing to the same HASH computed on the image contents.
   *
   * @param string $uri
   *   A string containing the uri to the resource to delete.
   *
   * @return bool
   *   TRUE if resource was successfully deleted.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    // don't delete derivatives...
    if (strpos($uri, 'rokka://styles/') === 0) {
      return TRUE;
    }
    $meta = $this->doGetMetadataFromUri($uri);
    $hash = $meta->getHash();
    $sharedHashesCount = $this->rokkaService->countImagesWithHash($hash);
    if ($sharedHashesCount > 1) {
      // If the same HASH is used elsewhere for another file..
      // Remove the Drupal image and FID, but don't remove the Rokka's image.
      $this->doPostSourceImageDeleted($meta);
      $this->logger->debug('Image file "{uri}" deleted, but kept in Rokka since HASH "{hash}" is not unique on Drupal.', [
        'uri' => $uri,
        'hash' => $meta->getHash(),
      ]);

      return TRUE;
    }

    $this->logger->debug('Deleting image file "{uri}".', [
      'uri' => $uri,
      'hash' => $meta->getHash(),
    ]);

    // Else, go on and let's our parent delete the Rokka image instance.
    return parent::unlink($uri);
  }

  /**
   * Callback function invoked after the underlying Stream has been unlinked and
   * the corresponding image deleted on Rokka.io
   * The callback receives the $hash used to remove the image.
   *
   * @param \Drupal\rokka\Entity\RokkaMetadata $meta
   *
   * @return bool
   */
  protected function doPostSourceImageDeleted(RokkaMetadata $meta) {
    return $meta->delete();
  }

  /**
   * @param string $uri
   * @param int $flags
   *
   * @return array|bool
   */
  public function url_stat($uri, $flags) {
    if ($this->is_dir($uri)) {
      return $this->formatUrlStat();
    }

    $meta = $this->doGetMetadataFromUri($uri);
    if ($meta) {
      $data = [
        'timestamp' => $meta->getCreatedTime(),
        'filesize' => $meta->getFilesize(),
      ];

      return $this->formatUrlStat($data);
    }

    return FALSE;
  }

  /**
   * @param string $uri
   *
   * @return bool
   */
  protected function is_dir($uri) {
    list($scheme, $target) = explode('://', $uri, 2);

    // Check if it's the root directory.
    if (empty($target)) {
      return TRUE;
    }

    // If not, check if the URI ends with '/' (eg: rokka://foldername/")
    return strrpos($target, '/') === (strlen($target) - 1);
  }

  /**
   * @return bool
   *   FALSE, as this stream wrapper does not support realpath().
   */
  public function realpath() {
    return FALSE;
  }

  /**
   * Gets the name of the directory from a given path.
   * A trailing "/" is always appended to mark the resource as a Directory.
   *
   * @param string $uri
   *   A URI.
   *
   * @return string
   *   A string containing the directory name.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL) {
    list($scheme, $target) = explode('://', $uri, 2);
    $target = $this->getTarget($uri);
    if (strpos($target, '/')) {
      // If we matched a directory here, let's append '/' in the end.
      $dirname = preg_replace('@/[^/]*$@', '', $target) . '/';
    }
    else {
      $dirname = '';
    }
    return $scheme . '://' . $dirname;
  }

  /**
   * Rokka has no support for mkdir(), thus we 'virtually' create them.
   *
   * @param string $uri
   *   A string containing the URI to the directory to create.
   * @param int $mode
   *   Permission flags - see mkdir().
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS and STREAM_MKDIR_RECURSIVE.
   *
   * @return bool
   *   TRUE if directory was successfully created.
   *
   * @see http://php.net/manual/en/streamwrapper.mkdir.php
   */
  public function mkdir($uri, $mode, $options) {
    // Some Drupal plugins call mkdir with a trailing slash. We mustn't store
    // that slash in the cache.
    $uri = rtrim($uri, '/');

    // clearstatcache(TRUE, $uri);
    //    // If this URI already exists in the cache, return TRUE if it's a folder
    //    // (so that recursive calls won't improperly report failure when they
    //    // reach an existing ancestor), or FALSE if it's a file (failure).
    //    $test_metadata = $this->readCache($uri);
    //    if ($test_metadata) {
    //      return (bool) $test_metadata['dir'];
    //    }
    //
    //    $metadata = $this->s3fs->convertMetadata($uri, []);
    //    $this->writeCache($metadata);
    // If the STREAM_MKDIR_RECURSIVE option was specified, also create all the
    // ancestor folders of this uri, except for the root directory.
    $parent_dir = \Drupal::service('file_system')->dirname($uri);
    if (($options & STREAM_MKDIR_RECURSIVE) && file_uri_target($parent_dir) != '') {
      return $this->mkdir($parent_dir, $mode, $options);
    }
    return TRUE;
  }

  /**
   * Rokka.io has no support for rmdir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to delete.
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   TRUE if the directory was successfully deleted.
   *
   *   Always return FALSE. (not supported)
   *
   * @see http://php.net/manual/en/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
    return FALSE;
  }

  /**
   * Rokka.io has no support for rename().
   *
   * @param string $from_uri
   *   The uri to the file to rename.
   * @param string $to_uri
   *   The new uri for file.
   *
   * @return bool
   *   Always returns FALSE. (not supported)
   *
   * @see http://php.net/manual/en/streamwrapper.rename.php
   */
  public function rename($from_uri, $to_uri) {
    return FALSE;
  }

  /**
   * Rokka.io has no support for opendir().
   *
   * @param string $uri
   *   A string containing the URI to the directory to open.
   * @param int $options
   *   Whether or not to enforce safe_mode (0x04).
   *
   * @return bool
   *   TRUE on success.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($uri, $options) {
    return $this->is_dir($uri);
  }

  /**
   * Rokka.io has no support for readdir().
   *
   * @return string|bool
   *   The next filename, or FALSE if there are no more files in the directory.
   *
   *   Always returns FALSE. (not supported)
   *
   * @see http://php.net/manual/en/streamwrapper.dir-readdir.php
   */
  public function dir_readdir() {
    return FALSE;
  }

  /**
   * Rokka.io has not support for rewinddir().
   *
   * @return bool
   *   TRUE on success.
   *
   *   Always returns FALSE. (not supported)
   *
   * @see http://php.net/manual/en/streamwrapper.dir-rewinddir.php
   */
  public function dir_rewinddir() {
    return FALSE;
  }

  /**
   * Rokka.io has no support for closedir().
   *
   * @return bool
   *   TRUE on success.
   *
   *   Always returns TRUE. (not supported)
   *
   * @see http://php.net/manual/en/streamwrapper.dir-closedir.php
   */
  public function dir_closedir() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Always returns FALSE.
   *
   * @see stream_select()
   * @see http://php.net/manual/streamwrapper.stream-cast.php
   */
  public function stream_cast($cast_as) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support touch(), chmod(), chown(), or chgrp().
   *
   * Manual recommends return FALSE for not implemented options, but Drupal
   * require TRUE in some cases like chmod for avoid watchdog erros.
   *
   * Returns FALSE if the option is not included in bypassed_options array
   * otherwise, TRUE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-metadata.php
   * @see \Drupal\Core\File\FileSystem::chmod()
   */
  public function stream_metadata($uri, $option, $value) {
    $bypassed_options = [STREAM_META_ACCESS];
    return in_array($option, $bypassed_options);
  }

  /**
   * {@inheritdoc}
   *
   * Since Windows systems do not allow it and it is not needed for most use
   * cases anyway, this method is not supported on local files and will trigger
   * an error and return false. If needed, custom subclasses can provide
   * OS-specific implementations for advanced use cases.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    trigger_error('stream_set_option() not supported for Rokka stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * This wrapper does not support stream_truncate.
   *
   * Always returns FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-truncate.php
   */
  public function stream_truncate($new_size) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Rokka');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Rokka Storage Service.');
  }

  /**
   * Return the local filesystem path.
   *
   * @return string
   *   The local path.
   */
  protected function getLocalPath($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    $path = str_replace('rokka://', '', $uri);
    $path = trim($path, '/');
    return $path;
  }

  /**
   * Callback function invoked after the underlying Stream has been flushed to
   * Rokka.io, the callback receives the SourceImage returned by the
   * $client->uploadSourceimage() invocation.
   *
   * @see RokkaStreamWrapper::stream_flush()
   *
   * @param \Rokka\Client\Core\SourceImage $sourceImage
   *
   * @return bool
   */
  protected function doPostSourceImageSaved(SourceImage $sourceImage) {
    // At this point the image has been uploaded to Rokka.io for the
    // "rokka://URI". Here we use our {rokka_metadata} table to store
    // the values returned by Rokka such as: hash, filesize, ...
    // First check if the URI is already tracked (i.e. the file has been overwritten).
    $meta = $this->doGetMetadataFromUri($this->uri);
    if ($meta) {

      // If the two images are the same we're done, just return true.
      if ($meta->getHash() == $sourceImage->hash) {
        $this->logger->debug('Image re-uploaded to Rokka for "{uri}": "{hash}" (hash did not change)', [
          'uri' => $this->uri,
          'hash' => $sourceImage->hash,
        ]);
        return TRUE;
      }

      $this->logger->debug('Image replaced on Rokka for "{uri}": "{hash}" (old hash: "{oldHash}")', [
        'uri' => $this->uri,
        'oldHash' => $meta->getHash(),
        'hash' => $sourceImage->hash,
      ]);

      // Update the RokkaMetadata with the new data coming from the uploaded image.
      $meta->hash = $sourceImage->hash;
      $meta->binary_hash = $sourceImage->binaryHash;
      $meta->created = $sourceImage->created->getTimestamp();
      $meta->filesize = $sourceImage->size;
      $meta->setHeight($sourceImage->height);
      $meta->setWidth($sourceImage->width);
      $meta->setFormat($sourceImage->format);
    }
    else {
      $this->logger->debug('New Image uploaded to Rokka for "{uri}": "{hash}"', [
        'uri' => $this->uri,
        'hash' => $sourceImage->hash,
      ]);

      $meta = RokkaMetadata::create([
        'uri' => $this->uri,
        'hash' => $sourceImage->hash,
        'binary_hash' => $sourceImage->binaryHash,
        'filesize' => $sourceImage->size,
        'created' => $sourceImage->created->getTimestamp(),
        'height' => $sourceImage->height,
        'width' => $sourceImage->width,
        'format' => $sourceImage->format,
      ]);
    }

    return $meta->save();
  }

  /**
   * Override the default exception handling, logging errors to Drupal messages
   * and Watchdog.
   *
   * @param \Exception[] $exceptions
   * @param mixed $flags
   *
   * @return bool
   *
   * @throws \Exception
   */
  protected function triggerException($exceptions, $flags = NULL) {
    $exceptions = is_array($exceptions) ? $exceptions : [$exceptions];

    /** @var \Exception $exception */
    foreach ($exceptions as $exception) {
      // If we got a GuzzleException here, means that something happened during
      // the data transfer. We throw the exception to Drupal.
      if ($exception instanceof GuzzleException) {
        drupal_set_message(t('An error occurred while communicating with the Rokka.io server!'), 'error');
      }

      $this->logger->critical(
        'Exception caught: {exceptionCode} "{exceptionMessage}". In {file} at line {line}.',
        [
          'exceptionCode' => $exception->getCode(),
          'exceptionMessage' => $exception->getMessage(),
          'file' => $exception->getFile(),
          'line' => $exception->getLine(),
        ]
      );
    }

    if (!($flags & STREAM_URL_STAT_QUIET)) {
      throw $exception;
    }

    return parent::triggerException($exceptions, $flags);
  }

}
