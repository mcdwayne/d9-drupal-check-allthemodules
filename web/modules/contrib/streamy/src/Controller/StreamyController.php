<?php

namespace Drupal\streamy\Controller;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\streamy\StreamyFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Class StreamyController
 *
 * @package   Controller
 *
 * @author
 * @copyright
 */
class StreamyController extends ControllerBase {

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
   * @var \Drupal\streamy\StreamyFactory
   */
  protected $streamyFactory;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * StreamyController constructor.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface                     $lock
   * @param \Psr\Log\LoggerInterface                                   $logger
   * @param \Drupal\streamy\StreamyFactory                             $streamyFactory
   * @param \Drupal\Core\File\FileSystemInterface                      $fileSystem
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   */
  public function __construct(LockBackendInterface $lock,
                              LoggerInterface $logger,
                              StreamyFactory $streamyFactory,
                              FileSystemInterface $fileSystem,
                              TransliterationInterface $transliteration) {
    $this->lock = $lock;
    $this->logger = $logger;
    $this->streamyFactory = $streamyFactory;
    $this->fileSystem = $fileSystem;
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lock'),
      $container->get('logger.channel.streamy'),
      $container->get('streamy.factory'),
      $container->get('file_system'),
      $container->get('transliteration')
    );
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\image\ImageStyleInterface         $image_style
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   If scheme or filepath are not provided.
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   If the image is already being generated.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function deliver(Request $request, ImageStyleInterface $image_style) {
    $filePath = $request->query->get('file');
    $scheme = $request->query->get('scheme');

    if (!$scheme) {
      throw new HttpException(500, 'scheme parameter must be supplied');
    }

    if (!$filePath) {
      throw new HttpException(500, 'file parameter must be supplied');
    }

    $imageUri = $scheme . "://{$filePath}";
    $derivativeExists = FALSE;
    if ($this->fileSystem->validScheme($scheme)) {
      $flySystemHelper = $this->streamyFactory->getFilesystem($scheme);
      if (!$flySystemHelper->has($imageUri)) {
        return new Response(NULL, 404);
      }

      $derivativeUri = $image_style->buildUri($imageUri);
      $derivativeExists = file_exists($derivativeUri);

      // Don't start generating the image if the derivative already exists or if
      // generation is in progress in another thread.
      if (!$derivativeExists) {
        // Fixes the issue with Chinese characters
        $filePath = $this->transliteration->transliterate($filePath);

        $lockName = 'image_style_deliver:' . $filePath . ':' . Crypt::hashBase64($imageUri);
        $lockAcquired = $this->lock->acquire($lockName);
        if (!$lockAcquired) {
          // Tell client to retry again in 3 seconds. Currently no browsers are
          // known to support Retry-After.
          throw new ServiceUnavailableHttpException(3, $this->t('Image generation in progress. Try again shortly.'));
        }

        $derivativeExists = $image_style->createDerivative($imageUri, $derivativeUri);
        $this->lock->release($lockName);
      }
    }
    if ($derivativeExists) {
      return $this->redirectToImage($derivativeUri);
    } else {
      $this->logger->notice('Unable to generate the derived image with scheme: %scheme and path: %path.',
                            ['%path' => $filePath, '%scheme' => $scheme]);

      return new Response($this->t('Error generating image.'), 500);
    }
  }

  /**
   * Redirect the user to the external image URL.
   *
   * @param $uri
   *
   * @return Response
   */
  private function redirectToImage($uri) {
    return new Response(
      NULL, 302, [
            'location'      => file_create_url($uri),
            'Cache-Control' => 'must-revalidate, no-cache, post-check=0, pre-check=0, private',
            'Expires'       => 'Sun, 19 Nov 1978 05:00:00 GMT',
          ]
    );
  }
}
