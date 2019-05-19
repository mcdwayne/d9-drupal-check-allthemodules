<?php

namespace Drupal\vendor_stream_wrapper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Vendor Stream Wrapper file controller.
 *
 * Sets up serving of files from the vendor directory, using the vendor://
 * stream wrapper.
 */
class VendorFileDownloadController extends ControllerBase implements VendorFileDownloadControllerInterface {

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The log service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Creates a new VendorFileDownloadController instance.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mimeTypeGuesser
   *   The MIME type guesser.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    MimeTypeGuesserInterface $mimeTypeGuesser,
    LoggerChannelFactoryInterface $loggerFactory
  ) {
    $this->mimeTypeGuesser = $mimeTypeGuesser;
    $this->logger = $loggerFactory->get('Vendor File Download'):
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.mime_type.guesser'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function download(Request $request) {
    $filepath = str_replace(':', '/', $request->get('filepath'));
    $scheme = 'vendor';
    $uri = $scheme . '://' . $filepath;

    $mime_type = '';
    try {
      $mime_type = $this->mimeTypeGuesser->guess($uri);
    }
    catch (\Exception $e) {
      $this->logger->error('Vendor file download error: %message', ['%message' => $exception->getMessage()]);
    }

    if (!empty($mime_type)) {
      $headers = [
        'Content-Type' => $mime_type,
      ];

      return new BinaryFileResponse($uri, 200, $headers, TRUE);
    }

    throw new NotFoundHttpException();
  }

}
