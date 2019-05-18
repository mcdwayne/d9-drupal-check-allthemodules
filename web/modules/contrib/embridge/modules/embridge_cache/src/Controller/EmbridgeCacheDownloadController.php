<?php
/**
 * @file
 * Contains \Drupal\embridge_cache\Controller\EmbridgeCacheDownloadController.
 */

namespace Drupal\embridge_cache\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\embridge\EmbridgeAssetEntityInterface;
use Drupal\embridge\EmbridgeCacheHelper;
use Drupal\embridge\EnterMediaAssetHelperInterface;
use Drupal\embridge\Entity\EmbridgeCatalog;
use Drupal\system\FileDownloadController;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Defines a controller to serve cache embridge assets.
 */
class EmbridgeCacheDownloadController extends FileDownloadController {

  /**
   * The asset entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $assetStorage;

  /**
   * The asset helper service.
   *
   * @var \Drupal\embridge\EnterMediaAssetHelperInterface
   */
  protected $assetHelper;

  /**
   * The cache helper service.
   *
   * @var \Drupal\embridge\EmbridgeCacheHelper
   */
  protected $cacheHelper;

  /**
   * Client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a ImageStyleDownloadController object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $asset_storage
   *   The asset entity storage.
   * @param \Drupal\embridge\EnterMediaAssetHelperInterface $asset_helper
   *   The asset helper service.
   * @param \Drupal\embridge\EmbridgeCacheHelper $cache_helper
   *   The cache helper service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   */
  public function __construct(
    EntityStorageInterface $asset_storage,
    EnterMediaAssetHelperInterface $asset_helper,
    EmbridgeCacheHelper $cache_helper,
    ClientInterface $http_client,
    FileSystem $file_system,
    LockBackendInterface $lock
  ) {
    $this->assetStorage = $asset_storage;
    $this->assetHelper = $asset_helper;
    $this->cacheHelper = $cache_helper;
    $this->httpClient = $http_client;
    $this->fileSystem = $file_system;
    $this->lock = $lock;
    $this->logger = $this->getLogger('embridge');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('embridge_asset_entity'),
      $container->get('embridge.asset_helper'),
      $container->get('embridge.cache_helper'),
      $container->get('http_client'),
      $container->get('file_system'),
      $container->get('lock')
    );
  }

  /**
   * Downloads an embridge asset given a conversion and file path.
   *
   * After caching an asset, transfer it to the requesting agent.
   *
   * TODO: Test this.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $scheme
   *   The file scheme.
   * @param string $conversion
   *   The image style to deliver.
   * @param \Drupal\embridge\Entity\EmbridgeCatalog $embridge_catalog
   *   The application id for the catalog the asset is in in EMDB.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\Response
   *   The transferred file as response or some error response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   Thrown when the file is still being generated.
   *
   * @see \Drupal\image\Controller\ImageStyleDownloadController::deliver
   */
  public function deliver(Request $request, $scheme, $conversion, EmbridgeCatalog $embridge_catalog) {
    // TODO: Implement private file caching, does this even apply?
    if ($scheme == 'private') {
      throw new AccessDeniedHttpException();
    }

    $catalog_conversions = $embridge_catalog->getConversionsArray();
    // Support old style system conversions.
    $conversion_exists = !empty($catalog_conversions[$conversion]) || !empty($catalog_conversions['system:' . $conversion]);
    // Check that the conversion is defined, the scheme is valid.
    $valid = !empty($conversion) && $conversion_exists &&  $this->fileSystem->validScheme($scheme);
    if (!$valid) {
      throw new AccessDeniedHttpException();
    }

    // Get the asset the url refers to.
    $target = $request->query->get('file');
    // The path will be source_path/filename.
    $asset_source = $this->fileSystem->dirname($target);
    $asset = NULL;
    $query = $this->assetStorage->getQuery();
    // Source paths can have a trailing slash.
    $or = $query->orConditionGroup();
    $or->condition('source_path', $asset_source . '/');
    $or->condition('source_path', $asset_source);
    $query->condition($or);
    $result = $query->execute();

    if (!empty($result)) {
      /** @var EmbridgeAssetEntityInterface $asset */
      $asset = $this->assetStorage->load(reset($result));
    }

    // If an asset couldn't be found, return access denied.
    if (!$asset) {
      throw new AccessDeniedHttpException();
    }

    $cached_uri = $this->cacheHelper->buildUri($embridge_catalog, $asset, $scheme, $conversion);

    // Don't start generating the asset if the cache already exists or if
    // generation is in progress in another thread.
    $lock_name = 'embridge_cache:' . $conversion . ':' . Crypt::hashBase64($target);
    if (!file_exists($cached_uri)) {
      $lock_acquired = $this->lock->acquire($lock_name);
      if (!$lock_acquired) {
        // Tell client to retry again in 3 seconds. Currently no browsers are
        // known to support Retry-After.
        throw new ServiceUnavailableHttpException(3, $this->t('EMBridge Cache generation in progress. Try again shortly.'));
      }
    }

    // Try to generate the image, unless another thread just did it while we
    // were acquiring the lock.
    $success = file_exists($cached_uri) || $this->downloadAsset($asset, $embridge_catalog->getApplicationId(), $conversion, $cached_uri);

    if (!empty($lock_acquired)) {
      $this->lock->release($lock_name);
    }
    $headers = [];
    if ($success) {
      $uri = $cached_uri;
      $headers += [
        'Content-Type' => $asset->getMimeType(),
        'Content-Length' => filesize($cached_uri),
      ];
      // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
      // sets response as not cacheable if the Cache-Control header is not
      // already modified. We pass in FALSE for non-private schemes for the
      // $public parameter to make sure we don't change the headers.
      return new BinaryFileResponse($uri, 200, $headers, $scheme !== 'private');
    }
    else {
      $this->logger->notice('Unable to generate the derived image located at %path.', array('%path' => $cached_uri));
      return new Response($this->t('Error generating image.'), 500);
    }
  }

  /**
   * Downloads an asset conversion into the local file system.
   *
   * @param \Drupal\embridge\EmbridgeAssetEntityInterface $asset
   *   The asset.
   * @param string $application_id
   *   The application id for the catalog the asset is in in EMDB.
   * @param string $conversion
   *   The conversion to fetch.
   * @param string $destination
   *   The destination uri.
   *
   * @return bool
   *   Whether the action was successful.
   */
  protected function downloadAsset(EmbridgeAssetEntityInterface $asset, $application_id, $conversion, $destination) {
    $directory = $this->fileSystem->dirname($destination);

    // Build the destination folder tree if it doesn't already exist.
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $this->logger->error('Failed to create cache directory: %directory', array('%directory' => $directory));
      return FALSE;
    }

    $asset_url = $this->assetHelper->getAssetConversionUrl($asset, $application_id, $conversion);
    try {
      $get_result = $this->httpClient->request('get', $asset_url);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to download image at %url with message "%message"', array('%url' => $asset_url, '%message' => $e->getMessage()));
      return FALSE;
    }
    $code = $get_result->getStatusCode();

    if (!empty($get_result->getBody()) && $code != 400 && $code != 500) {
      if (file_unmanaged_save_data($get_result->getBody()->getContents(), $destination, FILE_EXISTS_REPLACE)) {
        return $this->fileSystem->chmod($destination);
      }
    }

    return FALSE;
  }

}
