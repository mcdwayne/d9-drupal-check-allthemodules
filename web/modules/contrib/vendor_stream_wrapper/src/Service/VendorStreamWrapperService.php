<?php

namespace Drupal\vendor_stream_wrapper\Service;

use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Provides services for the Vendor Stream Wrapper module.
 */
class VendorStreamWrapperService implements VendorStreamWrapperServiceInterface {

  /**
   * The Stream Wrapper Service.
   *
   * @var Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperService;

  /**
   * Constructor.
   */
  public function __construct(StreamWrapperManagerInterface $streamWrapperManager) {
    $this->streamWrapperService = $streamWrapperManager;
  }

  /**
   * {@inheritdoc}
   */
  public function creatUrlFromUri($uri) {
    if (strpos($uri, 'vendor://') === 0) {
      if ($wrapper = $this->streamWrapperService->getViaUri($uri)) {
        return $wrapper->getExternalUrl();
      }
    }
    else {
      return $uri;
    }
  }

}
