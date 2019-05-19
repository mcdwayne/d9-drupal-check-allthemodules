<?php

namespace Drupal\streamy\Fallback;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\streamy\StreamyFactory;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class StreamyFallbackQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
   * QueueWorker constructor.
   *
   * @param \Psr\Log\LoggerInterface       $logger
   * @param \Drupal\streamy\StreamyFactory $streamyFactory
   */
  public function __construct(LoggerInterface $logger, StreamyFactory $streamyFactory) {
    $this->logger = $logger;
    $this->streamyFactory = $streamyFactory;
  }

  /**
   * @inheritdoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('logger.channel.streamy'),
      $container->get('streamy.factory')
    );
  }

  /**
   * @inheritdoc
   */
  public function processItem($data) {
    $scheme = $data->scheme;
    $filePath = $data->filePath;

    try {
      $this->checkMandatoryInputs($scheme, $filePath);
      $fileSystemHelper = $this->streamyFactory->getFilesystem($scheme);
      if ($fileSystemHelper) {
        /** @var $fileSystemHelper \Drupal\streamy\StreamWrapper\FlySystemHelper */
        $currentFileSystem = $fileSystemHelper->getWriteFileSystem();

        /** @var $adapter \League\Flysystem\Replicate\ReplicateAdapter */
        $adapter = $currentFileSystem instanceof MountManager ? $currentFileSystem->getAdapter($scheme . '://') : NULL;

        $masterAdapter = $adapter->getSourceAdapter();
        $slaveAdapter = $adapter->getReplicaAdapter();
        $this->copyFileIfDoesNotExists($masterAdapter, $slaveAdapter, $scheme, $filePath);
      }
    } catch (\Exception $e) {
      $this->logger->warning('Unable to fallback copy the file with scheme: %scheme and path: %path from slave to master. Error: %error_message',
                             ['%path' => $filePath, '%scheme' => $scheme, '%error_message' => $e->getMessage()]);
    }
  }

  /**
   * @param string $scheme
   * @param string $filePath
   * @throws \InvalidArgumentException
   */
  protected function checkMandatoryInputs($scheme, $filePath) {
    if (!is_scalar($scheme) || !trim($scheme)) {
      throw new \InvalidArgumentException('scheme parameter must be supplied');
    }

    if (!is_scalar($filePath) || !trim($filePath)) {
      throw new \InvalidArgumentException('file parameter must be supplied');
    }
  }

  /**
   * @param \League\Flysystem\AdapterInterface $masterAdapter
   * @param \League\Flysystem\AdapterInterface $slaveAdapter
   * @param string                             $scheme
   * @param string                             $filePath
   * @return bool
   */
  protected function copyFileIfDoesNotExists(AdapterInterface $masterAdapter, AdapterInterface $slaveAdapter, string $scheme, string $filePath) {
    $masterFilesystem = $this->getFileSystem($masterAdapter);
    $slaveFileSystem = $this->getFileSystem($slaveAdapter);
    $masterMount = $this->getMountManager([$scheme => $masterFilesystem]);

    if (!$this->fileExists($masterMount, $scheme, $filePath)) {
      $mount = $this->getMountManager(['master' => $masterFilesystem, 'slave' => $slaveFileSystem]);
      return $this->copyFileToMaster($filePath, $mount);
    }
    return FALSE;
  }

  /**
   * @param array $settings
   * @return \League\Flysystem\MountManager
   */
  protected function getMountManager(array $settings) {
    return new MountManager($settings);
  }

  /**
   * @param \League\Flysystem\AdapterInterface $adapter
   * @return \League\Flysystem\Filesystem
   */
  protected function getFileSystem(AdapterInterface $adapter) {
    return new Filesystem($adapter);
  }

  /**
   * @param \League\Flysystem\MountManager $mount
   * @param string                         $scheme
   * @param string                         $filePath
   * @return bool
   */
  protected function fileExists(MountManager $mount, string $scheme, string $filePath) {
    return $mount->has($scheme . '://' . $filePath);
  }

  /**
   * @param string                         $filePath
   * @param \League\Flysystem\MountManager $mount
   * @return bool
   */
  protected function copyFileToMaster(string $filePath, MountManager $mount) {
    return $mount->copy('slave://' . $filePath, 'master://' . $filePath);
  }

}
