<?php

namespace Drupal\ib_dam;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\FileInterface;
use Drupal\ib_dam\Asset\AssetInterface;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadDestination;
use Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\uri_template as uri_template;

/**
 * Downloader service.
 *
 * Used to download asset files and thumbnails using IntelligenceBank DAM API.
 *
 * @package Drupal\ib_dam
 */
class Downloader {

  use StringTranslationTrait;

  const THUMBNAIL_BASE_URL = 'https://apius.intelligencebank.com/webapp/1.0/icon';

  protected $uuidComponent;
  protected $fileSystem;
  protected $logger;
  protected $config;
  protected $api;

  /**
   * Constructs Downloader object.
   */
  public function __construct(
    IbDamApi $api,
    ConfigFactoryInterface $config_factory,
    LoggerChannelInterface $logger_chanel,
    FileSystemInterface $file_system,
    UuidInterface $uuid_component
  ) {
    $this->api = $api;
    $this->config = $config_factory->get('id_dam.settings');
    $this->logger = $logger_chanel;
    $this->fileSystem = $file_system;
    $this->uuidComponent = $uuid_component;
  }

  /**
   * Download asset file.
   *
   * Fetch file stream from api and save as unmanaged local file.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset object where take resource url.
   * @param string $upload_dir
   *   The file dir uri where store unmanaged file.
   *
   * @return bool|null
   *   Result of download operation.
   */
  public function download(AssetInterface $asset, $upload_dir) {
    $asset_source = $asset->source();
    $response     = $this->api
      ->setAuthKey($asset_source->getAuthKey())
      ->fetchResource($asset_source->getUrl());

    if (!$response instanceof ResponseInterface) {
      (new AssetDownloaderBadResponse())->logException()
        ->displayMessage();
      return FALSE;
    }

    try {
      $status = $this->saveUnmanagedFile(
        $response,
        $upload_dir,
        $asset_source->getFileName()
      );
    }
    catch (AssetDownloaderBadDestination $e) {
      $e->logException()->displayMessage();
      return FALSE;
    }
    catch (AssetDownloaderBadResponse $e) {
      $e->logException()->displayMessage();
      return FALSE;
    }

    return $status;
  }

  /**
   * Set correct file permissions.
   */
  public function setFilePermission(FileInterface $file) {
    $this->fileSystem->chmod($file->getFileUri());
  }

  /**
   * Fetch asset thumbnail file and save as umnanaged local file.
   *
   * @param \Drupal\ib_dam\Asset\AssetInterface $asset
   *   The asset object where take thumbnail remote url.
   * @param string $upload_dir
   *   The file dir uri where store unmanaged file.
   *
   * @return bool|null
   *   Result of download operation.
   */
  public function downloadThumbnail(AssetInterface $asset, $upload_dir) {
    $thumb_uri = static::buildThumbnailUrl($asset->source()->getUrl());
    $response = $this->api
      ->setAuthKey($asset->source()->getAuthKey())
      ->fetchResource($thumb_uri);

    $extension     = 'png';
    $discrete_type = 'image';

    if (!$response instanceof ResponseInterface) {
      (new AssetDownloaderBadResponse())->logException()
        ->displayMessage();
      return FALSE;
    }

    if ($response->hasHeader('Content-Type')) {
      $content_type = $response->getHeader('Content-Type');
      $mimetype = reset($content_type);
      list(, $extension) = explode('/', $mimetype, 2);
      $discrete_type = static::getSourceTypeFromMime($mimetype);
    }

    $guid = $this->uuidComponent->generate();
    $filename = "ib_thumb_$guid.$extension";

    $result = FALSE;

    if ($discrete_type == 'image') {
      try {
        $result = $this->saveUnmanagedFile($response, $upload_dir, $filename);
      }
      catch (AssetDownloaderBadDestination $e) {
        $e->logException()->displayMessage();
        return FALSE;
      }
      catch (AssetDownloaderBadResponse $e) {
        $e->logException()->displayMessage();
        return FALSE;
      }
    }

    return $result;
  }

  /**
   * Helper function to prepare file directory and save upload.
   *
   * Fetch file data from HTTP stream.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The HTTP response object.
   * @param string $directory
   *   The upload directory path.
   * @param string $filename
   *   The file name of file that will be saved.
   *
   * @return bool|null
   *   Result of save operation.
   *
   * @throws \Drupal\ib_dam\Exceptions\AssetDownloaderBadDestination
   * @throws \Drupal\ib_dam\Exceptions\AssetDownloaderBadResponse
   */
  private function saveUnmanagedFile(ResponseInterface $response, $directory, $filename) {
    $stream_data = $response->getBody();

    if (substr($directory, -1) != '/') {
      $directory .= '/';
    }
    $destination = file_destination($directory . $filename, FILE_EXISTS_RENAME);

    if (!$destination) {
      throw new AssetDownloaderBadDestination($directory, $filename);
    }

    try {
      $status = file_unmanaged_save_data((string) $stream_data, $destination);
    }
    catch (\Exception $e) {
      throw new AssetDownloaderBadResponse($e->getMessage());
    }

    if (!$status) {
      throw new AssetDownloaderBadDestination($directory, $filename);
    }
    return $status;
  }

  /**
   * Construct special thumbnail remote url from asset remote url.
   */
  private static function buildThumbnailUrl($origin_url) {
    parse_str(parse_url($origin_url, PHP_URL_QUERY), $source_params);

    $params = [
      'p10'  => $source_params['p10'],
      'p20'  => $source_params['p20'],
      'name' => $source_params['fileuuid'],
      'type' => 'file',
    ];

    $template = implode('&', [
      'p10={p10}',
      'p20={p20}',
      'type={type}',
      'name={name}',
    ]);

    return self::THUMBNAIL_BASE_URL . '?' . uri_template($template, $params);
  }

  /**
   * Useful helper function to get "right" asset type from mimetype.
   *
   * Some of resources aren't as they should be,
   * for example image/vnd.photoshop.. isn't image
   * that can be easy rendered in a site.
   * The same thing for svg files, it's rather file than image.
   *
   * Also some image isn't supported by current site image toolkit.
   */
  public static function getSourceTypeFromMime($mime) {
    $image_factory = \Drupal::service('image.factory');
    $supported_image_types = $image_factory->getSupportedExtensions();

    list($type, $subtype) = explode('/', $mime);

    $asset_type = $type;

    if ($type === 'image') {
      if (strpos('vnd', $subtype) !== FALSE) {
        $asset_type = 'file';
      }
      else {
        switch ($subtype) {

          case 'webp':
          case 'svg+xml':
            $asset_type = 'file';
            break;

          default:
            $asset_type = in_array($subtype, $supported_image_types)
              ? 'image'
              : 'file';
            break;
        }
      }
    }
    elseif ($type === 'application') {
      $asset_type = 'file';
    }
    return $asset_type;
  }

}
