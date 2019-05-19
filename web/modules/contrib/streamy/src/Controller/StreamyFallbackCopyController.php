<?php

namespace Drupal\streamy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\streamy\StreamyFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Replicate\ReplicateAdapter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class StreamyController
 *
 * @package   Controller
 *
 * @author
 * @copyright
 */
class StreamyFallbackCopyController extends ControllerBase {

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
   * StreamyFallbackCopyController constructor.
   *
   * @param \Psr\Log\LoggerInterface       $logger
   * @param \Drupal\streamy\StreamyFactory $streamyFactory
   */
  public function __construct(LoggerInterface $logger,
                              StreamyFactory $streamyFactory) {
    $this->logger = $logger;
    $this->streamyFactory = $streamyFactory;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.streamy'),
      $container->get('streamy.factory')
    );
  }

  /**
   * Creates a file on the master stream if this is only present on the slave stream.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   If scheme or filepath are not provided.
   * @throws \Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException
   *   If the image is already being generated.
   * @return array
   */
  public function fallback(Request $request) {
    $filePath = trim($request->query->get('file'), '/');
    $scheme = $request->query->get('scheme');

    if (!$scheme) {
      throw new HttpException(500, 'scheme parameter must be supplied');
    }

    if (!$filePath) {
      throw new HttpException(500, 'file parameter must be supplied');
    }

    try {
      $fileSystemHelper = $this->streamyFactory->getFilesystem($scheme);
      if ($fileSystemHelper) {
        $fs = $fileSystemHelper->getWriteFileSystem();
        $adapter = $fs instanceof MountManager ? $fs->getAdapter($scheme . '://') : NULL;
        if ($adapter instanceof ReplicateAdapter) {
          // Retrieving the single adapters to access their filesystems
          $masterAdapter = $adapter->getSourceAdapter();
          $slaveAdapter = $adapter->getReplicaAdapter();

          $masterFilesystem = new Filesystem($masterAdapter);
          $masterMount = new MountManager([$scheme => $masterFilesystem]);
          if (!$masterMount->has($scheme . '://' . $filePath)) {
            $bothMount = new MountManager(['master' => $masterFilesystem, 'slave' => new Filesystem($slaveAdapter)]);
            $bothMount->copy('slave://' . $filePath, 'master://' . $filePath);
          }
        }
      }
    } catch (Exception $e) {
      $this->logger->notice('Unable to fallback copy the file with scheme: %scheme and path: %path from slave to master. Error: %error_message',
                            ['%path' => $filePath, '%scheme' => $scheme, '%error_message' => $e->getMessage()]);
      return new JsonResponse(['error while copying'], 500);
    } catch (\Exception $e) {
      $this->logger->notice('Unable to fallback copy the file with scheme: %scheme and path: %path from slave to master. Error: %error_message',
                            ['%path' => $filePath, '%scheme' => $scheme, '%error_message' => $e->getMessage()]);
      return new JsonResponse(['error while copying'], 500);
    }
    return new JsonResponse(['success'], 200);
  }

}
