<?php

namespace Drupal\az_blob_fs;


use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Internal\Authentication\SharedAccessSignatureAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Authentication\SharedKeyAuthScheme;
use MicrosoftAzure\Storage\Common\Internal\Middlewares\CommonRequestMiddleware;
use MicrosoftAzure\Storage\Blob\Internal\BlobResources as Resources;
use MicrosoftAzure\Storage\Common\Internal\StorageServiceSettings;
use MicrosoftAzure\Storage\Common\Internal\Utilities;
use function watchdog_exception;

class BlobRestProxyAlter extends BlobRestProxy {

  /**
   * Creates full URI to the given blob.
   *
   * @param string $container The container name.
   * @param string $blob The blob name.
   *
   * @return string
   */
  public function _getBlobUrl($container, $blob) {
    $encodedBlob = $this->_createPath($container, $blob);
    $uri = $this->getPsrPrimaryUri();
    $exPath = $uri->getPath();

    if ($exPath != '') {
      //Remove the duplicated slash in the path.
      $encodedBlob = str_replace('//', '/', $exPath . $encodedBlob);
    }

    return (string) $uri->withPath($encodedBlob);
  }

  private function _createPath($container, $blob = '') {
    if (empty($blob)) {
      if (empty($container)) {
        $separator = '/';
      }
      else {
        $separator = '';
      }
      return $separator . $container;
    }
    else {
      $encodedBlob = urlencode($blob);
      // Unencode the forward slashes to match what the server expects.
      $encodedBlob = str_replace('%2F', '/', $encodedBlob);
      // Unencode the backward slashes to match what the server expects.
      $encodedBlob = str_replace('%5C', '/', $encodedBlob);
      // Re-encode the spaces (encoded as space) to the % encoding.
      $encodedBlob = str_replace('+', '%20', $encodedBlob);
      // Empty container means accessing default container
      if (empty($container)) {
        $separator = '';
      }
      else {
        $separator = '/';
      }
      return $separator . $container . $separator . $encodedBlob;
    }
  }

  /**
   * 'Renames' a blob. Actually makes a copy of it and removes the old one.
   *
   * @param $source_container
   * @param $source_name
   * @param $destination_container
   * @param $destination_name
   *
   * @return bool
   */
  function renameBlob($source_container, $source_name, $destination_container, $destination_name) {
    try {
      $this->copyBlob($destination_container, $destination_name, $source_container, $source_name);
      $this->deleteBlob($source_container, $source_name);
    } catch (ServiceException $e) {
      watchdog_exception('Azure Blob File System', $e);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Indicates if provided uri is a file.
   * This method only check if the uri has file extension.
   * It does not validate the existence of the file or any
   * other truths. As far as I know, file with no extension are not
   * supported by default in the Drupal admin.
   *
   * @param string $uri
   *   The uri to validate.
   *
   * @return bool
   *   Returns TRUE if the uri is a file, FALSE otherwise.
   */
  public function uriIsFile($uri) {
    $parts = explode('/', $uri);
    $file_name = end($parts);
    return stripos($file_name, '.') !== FALSE;
  }

  public function getPrefixedBlob($container, $uri) {
    try {
      return $this->getBlob($container, $uri);
    }
    catch (ServiceException $e) {
      return FALSE;
    }
  }

  public static function createBlobService(
    $connectionString,
    array $options = []
  ) {
    $settings = StorageServiceSettings::createFromConnectionString(
      $connectionString
    );

    $primaryUri = Utilities::tryAddUrlScheme(
      $settings->getBlobEndpointUri()
    );

    $secondaryUri = Utilities::tryAddUrlScheme(
      $settings->getBlobSecondaryEndpointUri()
    );

    $blobWrapper = new BlobRestProxyAlter(
      $primaryUri,
      $secondaryUri,
      $settings->getName(),
      $options
    );

    // Getting authentication scheme
    if ($settings->hasSasToken()) {
      $authScheme = new SharedAccessSignatureAuthScheme(
        $settings->getSasToken()
      );
    }
    else {
      $authScheme = new SharedKeyAuthScheme(
        $settings->getName(),
        $settings->getKey()
      );
    }

    // Adding common request middleware
    $commonRequestMiddleware = new CommonRequestMiddleware(
      $authScheme,
      Resources::STORAGE_API_LATEST_VERSION,
      Resources::BLOB_SDK_VERSION
    );
    $blobWrapper->pushMiddleware($commonRequestMiddleware);

    return $blobWrapper;
  }

}