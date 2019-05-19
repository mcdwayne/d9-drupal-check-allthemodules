<?php

namespace Drupal\webp;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class Webp.
 *
 * @package Drupal\webp
 */
class Webp {

  use StringTranslationTrait;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Default image processing quality.
   *
   * @var int
   */
  protected $defaultQuality;

  /**
   * Webp constructor.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   Image factory to be used.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger channel factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String translation interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   */
  public function __construct(ImageFactory $imageFactory, LoggerChannelFactoryInterface $loggerFactory, TranslationInterface $stringTranslation, ConfigFactoryInterface $configFactory) {
    $this->imageFactory = $imageFactory;
    $this->logger = $loggerFactory->get('webp');
    $this->setStringTranslation($stringTranslation);
    $this->defaultQuality = $configFactory->get('webp.settings')->get('quality');
  }

  /**
   * Creates a WebP copy of a source image URI.
   *
   * @param string $uri
   *   Image URI.
   * @param int $quality
   *   Image quality factor (optional).
   *
   * @return bool|string
   *   The location of the WebP image if successful, FALSE if not successful.
   */
  public function createWebpCopy($uri, $quality = NULL) {
    $webp = FALSE;

    // Generate a GD resource from the source image. You can't pass GD resources
    // created by the $imageFactory as a parameter to another function, so we
    // have to do everything in one function.
    $sourceImage = $this->imageFactory->get($uri, 'gd');
    /** @var \Drupal\system\Plugin\ImageToolkit\GDToolkit $toolkit */
    $toolkit = $sourceImage->getToolkit();
    $mimeType = $sourceImage->getMimeType();
    $sourceImage = $toolkit->getResource();

    // If we can generate a GD resource from the source image, generate the URI
    // of the WebP copy and try to create it.
    if ($sourceImage !== NULL && $mimeType !== 'image/png') {

      $pathInfo = pathinfo($uri);
      $destination = strtr('@directory/@filename.@extension.webp', [
        '@directory' => $pathInfo['dirname'],
        '@filename' => $pathInfo['filename'],
        '@extension' => $pathInfo['extension'],
      ]);

      if (is_null($quality)) {
        $quality = $this->defaultQuality;
      }

      if (@imagewebp($sourceImage, $destination, $quality)) {
        // In some cases, libgd generates broken images. See
        // https://stackoverflow.com/questions/30078090/imagewebp-php-creates-corrupted-webp-files
        // for more information.
        if (filesize($destination) % 2 == 1) {
          file_put_contents($destination, "\0", FILE_APPEND);
        }

        @imagedestroy($sourceImage);
        $webp = $destination;
      }
      else {
        $error = $this->t('Could not generate WebP image.');
        $this->logger->error($error);
      }
    }
    // If we can't generate a GD resource from the source image, fail safely.
    else {
      $error = $this->t('Could not generate image resource from URI @uri.', [
        '@uri' => $uri,
      ]);
      $this->logger->error($error);
    }

    return $webp;
  }

  /**
   * Deletes all image style derivatives.
   */
  public function deleteImageStyleDerivatives() {
    // Remove the styles directory and generated images.
    if (@!file_unmanaged_delete_recursive(file_default_scheme() . '://styles')) {
      $error = $this->t('Could not delete image style directory while uninstalling WebP. You have to delete it manually.');
      $this->logger->error($error);
    }
  }

}
