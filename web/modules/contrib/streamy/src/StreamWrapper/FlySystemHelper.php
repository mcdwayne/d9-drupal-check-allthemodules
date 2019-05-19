<?php

namespace Drupal\streamy\StreamWrapper;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\streamy\StreamyCDNBase;
use Drupal\streamy\StreamyCDNManager;
use Drupal\streamy\StreamyStreamInterface;
use Drupal\streamy\StreamyStreamManager;
use GuzzleHttp\Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Plugin\GetWithMetadata;
use League\Flysystem\Replicate\ReplicateAdapter;
use Litipk\Flysystem\Fallback\FallbackAdapter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Twistor\Flysystem\Plugin\Mkdir;
use Twistor\Flysystem\Plugin\Rmdir;
use Twistor\Flysystem\Plugin\Stat;
use Twistor\Flysystem\Plugin\Touch;

/**
 * Class FlySystemHelper
 *
 * Provides direct methods interfaced with the FlySystem library.
 * Creates a mount with a Replica adapter called $writeFileSystem
 * and a mount with a Fallback adapter called $readFileSystem.
 * Replica mount is mainly used in the write operations, Fallback mount
 * is mainly used in the read operations.
 *
 * @package Drupal\streamy\StreamWrapper
 */
class FlySystemHelper {

  use StreamyURLTrait;

  /**
   * The current schemeName in use.
   *
   * @var string
   */
  protected $schemeName;

  /**
   * @var \Drupal\streamy\StreamyStreamManager
   */
  protected $streamyStreamManager;

  /**
   * @var \Drupal\streamy\StreamyCDNManager
   */
  protected $streamyCDNManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The current stream plugins.
   *
   * @var array
   */
  protected $streamPlugins = [];

  /**
   * The current available FS adapters from the available plugins type.
   *
   * @var array
   */
  protected $adapters = [];

  /**
   * @var \League\Flysystem\MountManager|null
   */
  protected $writeFileSystem;

  /**
   * @var \League\Flysystem\MountManager|null
   */
  protected $readFileSystem;

  /**
   * @var array
   */
  protected $cdnPlugin;

  /**
   * @var bool
   */
  protected $isPrivate;

  /**
   * @var bool
   */
  protected $isDisabled;

  /**
   * @var bool
   */
  protected $fallbackCopyIsDisabled;

  /**
   * @var \GuzzleHttp\Client
   */
  protected $http_client;

  /**
   * The default configuration.
   *
   * @var array
   */
  protected $defaultConfiguration = [
    'permissions' => [
      'dir'  => [
        'private' => 0700,
        'public'  => 0777,
      ],
      'file' => [
        'private' => 0600,
        'public'  => 0644,
      ],
    ],
    'metadata'    => ['timestamp', 'size', 'visibility'],
    'public_mask' => 0044,
  ];

  /**
   * The configuration of the current scheme.
   *
   * @var array
   */
  protected $configuration;

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * FlySystemHelper constructor.
   *
   * @param string                                     $scheme
   * @param \Drupal\streamy\StreamyStreamManager       $streamyStreamManager
   * @param \Drupal\streamy\StreamyCDNManager          $streamyCDNManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Psr\Log\LoggerInterface                   $logger
   * @param array                                      $configuration
   * @param \GuzzleHttp\Client                         $http_client
   * @param \Drupal\Core\Queue\QueueFactory            $queueFactory
   */
  public function __construct(string $scheme,
                              StreamyStreamManager $streamyStreamManager,
                              StreamyCDNManager $streamyCDNManager,
                              ConfigFactoryInterface $configFactory,
                              LoggerInterface $logger,
                              $configuration = [],
                              Client $http_client,
                              QueueFactory $queueFactory) {
    $this->isPrivate = isset($configuration['private']) &&
                       $configuration['private'] === TRUE ? TRUE : FALSE;
    $this->streamyStreamManager = $streamyStreamManager;
    $this->streamyCDNManager = $streamyCDNManager;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
    $this->http_client = $http_client;
    $this->configuration = array_merge($configuration, $this->defaultConfiguration);
    $this->setSchemeName($scheme);

    $this->isDisabled = $this->checkStreamIsDisabled($this->schemeName);
    $this->fallbackCopyIsDisabled = $this->checkFallbackCopyIsDisabled($this->schemeName);
    $this->queueFactory = $queueFactory;

    if (!$this->isDisabled) {
      $pluginsIds = $this->getSelectedPluginsIds();
      $this->adapters = $this->getAvailableAdapters($pluginsIds);

      $this->writeFileSystem = $this->getMountReplica($this->schemeName, $this->adapters);
      $this->readFileSystem = $this->getMountFallback($this->schemeName, $this->adapters);
      $this->cdnPlugin = $this->getCDNPlugin($this->schemeName);
    }
  }

  /**
   * @return \League\Flysystem\MountManager|null
   */
  public function getWriteFileSystem() {
    return $this->writeFileSystem;
  }

  /**
   * @return \League\Flysystem\MountManager|null
   */
  public function getReadFileSystem() {
    return $this->readFileSystem;
  }

  /**
   * @return mixed|null
   */
  public function getStreamPublicName() {
    return isset($this->configuration['name']) ? $this->configuration['name'] :
      NULL;
  }

  /**
   * @return mixed|null
   */
  public function getStreamPublicDescription() {
    return isset($this->configuration['description']) ?
      $this->configuration['description'] :
      NULL;
  }

  /**
   * @return bool
   */
  public function isDisabled() {
    return $this->isDisabled;
  }

  /**
   * Ensures that any plugin is correctly configured in order to use the Stream.
   * If the stream is disabled it will return FALSE straightaway.
   *
   * @return bool
   */
  public function ensure() {
    if ($this->isDisabled) {
      return FALSE;
    }
    if (!$this->ensureStreamPlugins()) {
      return FALSE;
    }
    if ($this->cdnPlugin instanceof StreamyCDNBase && !$this->ensureCDNPlugin()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @return bool
   */
  protected function ensureStreamPlugins() {
    $ensure = FALSE;

    foreach ($this->streamPlugins as $level => $streamPlugin) {
      $plugin = $streamPlugin['plugin'];
      if ($plugin instanceof StreamyStreamInterface) {
        $mountManager = $plugin->ensure($this->schemeName, $level);

        if (!$mountManager instanceof MountManager) {
          return FALSE;
        } else {
          $ensure = TRUE;
        }
      }
    }
    return $ensure;
  }

  /**
   * @return bool
   */
  protected function ensureCDNPlugin() {
    if ($this->cdnPlugin instanceof StreamyCDNBase) {
      $ensureResult = $this->cdnPlugin->ensure($this->schemeName);
      if (!$ensureResult) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @return string
   */
  public function getSchemeName() {
    return $this->schemeName;
  }

  /**
   * @return bool
   */
  public function isPrivate() {
    return $this->isPrivate;
  }

  /**
   * @param string $schemeName
   * @throws \Symfony\Component\Config\Definition\Exception\Exception
   *   If schemeName doesn't pass the regular expression.
   */
  protected function setSchemeName(string $schemeName) {
    // Only lowercase, uppercase letters and hyphens are admitted.
    if (!preg_match('"^[a-zA-Z\-]+$"', $schemeName)) {
      $this->logger->alert('The schema name provided is not in the right format: %streamName', ['%streamName' => $schemeName]);
      throw new Exception('Schema name is not in the right format');
    }
    $this->schemeName = strtolower($schemeName);
  }

  /**
   * Returns the IDs of the StreamyStream plugins selected in the Streamy configuration.
   *
   * @return array
   */
  protected function getSelectedPluginsIds() {
    $pluginsIds = [];
    $streamyConfig = (array) $this->configFactory->get('streamy.streamy')->get('plugin_configuration');

    $allowedKeys = ['master', 'slave'];
    if ($streamyConfig && isset($streamyConfig[$this->schemeName])) {
      foreach ($streamyConfig[$this->schemeName] as $k => $config) {
        if (in_array($k, $allowedKeys)) {
          $pluginsIds[$k] = $config;
        }
      }
    }
    return $pluginsIds;
  }

  /**
   * Checks whether this stream is disabled in the form settings.
   *
   * @param $scheme
   * @return bool
   */
  protected function checkStreamIsDisabled($scheme) {
    $isDisabled = TRUE;
    $config = (array) $this->configFactory->get('streamy.streamy')->get('plugin_configuration');
    if (isset($config[$scheme]) && isset($config[$scheme]['enabled'])) {
      $isDisabled = (int) $config[$scheme]['enabled'] === 1 ? FALSE : TRUE;
    }
    return $isDisabled;
  }

  /**
   * Checks whether this stream should use a fallback copy or not.
   *
   * @param $scheme
   * @return bool
   */
  protected function checkFallbackCopyIsDisabled($scheme) {
    $isDisabled = FALSE;
    $config = (array) $this->configFactory->get('streamy.streamy')->get('plugin_configuration');
    if (isset($config[$scheme]) && isset($config[$scheme]['disableFallbackCopy'])) {
      $isDisabled = (int) $config[$scheme]['disableFallbackCopy'] === 1 ? TRUE : FALSE;
    }
    return $isDisabled;
  }

  /**
   * Creates an instance of each StreamyStream plugin id and
   * returns the Adapters by calling the method getAdapter()
   * on each StreamyStream plugin id passed in the array $pluginsIds.
   *
   * @param $pluginsIds
   *                         The array value contains the StreamyStream plugin id.
   * @return array
   *                         An instance of each adapter passed in $pluginsIds.
   */
  protected function getAvailableAdapters($pluginsIds) {
    $adapters = [];

    foreach ($pluginsIds as $level => $stream) {
      if (is_string($stream) && $this->streamyStreamManager->hasDefinition($stream)) {
        $plugin = $this->streamyStreamManager->createInstance($stream);

        // Adding the current plugin in the streamPlugin array.
        $this->streamPlugins[$level]['plugin'] = $plugin;

        $adapter = $this->getAdapterFromPlugin($plugin, $level, $this->schemeName);

        if ($adapter instanceof AdapterInterface) {
          $adapters[$level]['name'] = $stream;
          $adapters[$level]['adapter'] = $adapter;
        }
      }
    }

    if (count($adapters) < 2) {
      $message = t('Streamy cannot find the two stream adapters necessary to work for the scheme <strong>%name</strong> (%scheme://), please verify your settings and try again. Refer to the log system for further information.',
                   ['%scheme' => $this->schemeName, '%name' => $this->getStreamPublicDescription()]);
      drupal_set_message($message, 'error');

      $message_log = t('Streamy cannot find the two stream adapters necessary to work for the scheme <strong>%name</strong> (%scheme://), please verify your settings and try again. Debug information: %streams',
                       ['%scheme' => $this->schemeName, '%streams' => print_r($pluginsIds, TRUE), '%name' => $this->getStreamPublicDescription()]);
      $this->logger->error($message_log);
    }

    return $adapters;
  }

  /**
   * @param \Drupal\Component\Plugin\PluginBase $plugin
   * @param                                     $schemeName
   * @return AdapterInterface|bool
   */
  protected function getAdapterFromPlugin(PluginBase $plugin, $level, $schemeName) {
    try {
      $adapter = $plugin->getAdapter($schemeName, $level);
    } catch (\Exception $e) {
      $adapter = FALSE;
    }
    return $adapter;
  }

  /**
   * @param $mountName
   * @param $adapters
   * @return \League\Flysystem\MountManager|null
   */
  protected function getMountReplica($mountName, $adapters) {
    $fileSystem = NULL;
    $replicaAdapters = $this->getFewAdapters($adapters, 2);
    if (count($replicaAdapters) >= 2) {
      $adapter = new ReplicateAdapter($replicaAdapters[0], $replicaAdapters[1]);
      $fileSystem = new Filesystem($adapter);

      $manager = new MountManager();
      $manager->mountFilesystem($mountName, $fileSystem);
      $this->addPluginsToManager($manager);

      // todo add event here to allow plugin insertion and other custom action
      return $manager;
    }
    return NULL;
  }

  /**
   * @param $mountName
   * @param $adapters
   * @return \League\Flysystem\MountManager|null
   */
  protected function getMountFallback($mountName, $adapters) {
    $fallbackAdapters = $this->getFewAdapters($adapters, 2);

    if (count($fallbackAdapters) >= 2) {
      $adapter = new FallbackAdapter($fallbackAdapters[0], $fallbackAdapters[1]);
      $fileSystem = new Filesystem($adapter);

      $manager = new MountManager();
      $manager->mountFilesystem($mountName, $fileSystem);
      $this->addPluginsToManager($manager);

      // todo add event here to allow plugin insertion and other custom action
      return $manager;
    }
    return NULL;
  }

  /**
   * @param \League\Flysystem\MountManager $manager
   */
  protected function addPluginsToManager(MountManager $manager) {
    $manager->addPlugin(new GetWithMetadata());
    $manager->addPlugin(new Mkdir());
    $manager->addPlugin(new Rmdir());
    $manager->addPlugin(new Stat($this->configuration['permissions'], $this->configuration['metadata']));
    $manager->addPlugin(new Touch());
  }

  /**
   * @param array $adapters
   * @param int   $adaptersNeeded
   * @return array
   */
  protected function getFewAdapters(array $adapters, int $adaptersNeeded = 2) {
    $adaptersNeeded--;
    $replicaAdapters = [];
    $i = 0;
    foreach ($adapters as $level => $adapterInfo) {
      $adapter = $adapterInfo['adapter'];
      if ($i > $adaptersNeeded) {
        break;
      }
      if ($adapter instanceof AdapterInterface) {
        $replicaAdapters[] = $adapter;
      }
      $i++;
    }
    return $replicaAdapters;
  }

  /**
   * @param $scheme
   * @return null|object
   */
  protected function getCDNPlugin($scheme) {
    $streamyConfig = (array) $this->configFactory->get('streamy.streamy')->get('plugin_configuration');
    $selectedCDN = isset($streamyConfig[$scheme]) ? $streamyConfig[$scheme]['cdn_wrapper'] : NULL;

    $plugin = NULL;
    if (is_string($selectedCDN) &&
        $this->streamyCDNManager->hasDefinition($selectedCDN)
    ) {
      $plugin = $this->streamyCDNManager->createInstance($selectedCDN);
    }

    return $plugin;
  }

  /**
   * @return array
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Calls the method in the given object and deals with the error by logging
   * it.
   *
   * @param       $object
   * @param       $method
   * @param array $args
   * @param null  $errorName
   * @return bool|mixed
   */
  protected function invokeCall($object, $method, $args = [], $errorName = NULL) {
    try {
      return call_user_func_array([$object, $method], $args);
    } catch (\Exception $e) {
      $errorName = $errorName ?: $method;
      $this->logError($errorName, $e, $args);
    }
    return FALSE;
  }

  /**
   * Logs the error by handling its class.
   *
   * @param string     $function
   * @param \Exception $e
   * @param array      $args
   */
  protected function logError($function, \Exception $e, $args = []) {
    switch (get_class($e)) {
      case 'League\Flysystem\FileNotFoundException':
        $path = isset($args[0]) && is_scalar($args[0]) ? $args[0] : NULL;

        // Distinguish between a "styles" missing image and another to avoid thousands of notice logs.
        if (strpos($path, '://styles/') !== FALSE) {
          // Do not log styles missing files
          return;
        } else {
          $logLevel = 'notice';
        }
        // TODO: Create a config option to shw log messages and re-enable this:
        // $this->logger->log($logLevel, '%s(): No such file or directory for the given path: %path', ['%s' => $function, '%path' => $path]);
        return;

      case 'League\Flysystem\RootViolationException':
        $path = isset($args[0]) && is_scalar($args[0]) ? $args[0] : NULL;
        $this->logger->notice('%s(): Cannot remove the root directory for the given path: %path', ['%s' => $function, '%path' => $path]);
        return;
    }

    // Don't allow any exceptions to leak.
    $this->logger->error($e->getMessage());
  }

  /**
   * @param $uri
   * @param $originalUri
   * @return mixed|null
   */
  public function getUrl($uri, $originalUri) {
    $url = NULL;

    // Private file is actually working but there is no logic that wraps
    // this behavior in backend
    if ($this->isPrivate) {
      if (!$this->fallbackCopyIsDisabled) {
        $this->queueAsyncFileCheck($originalUri);
      }
      $url = $this->getPrivateExternalURL($originalUri);
      return $url;
    }

    if ($this->cdnPlugin instanceof StreamyCDNBase) {
      $url = $this->cdnPlugin->getExternalUrl($uri, $this->schemeName);
    } else {
      // todo should this run even if CDN is enabled?
      if (!$this->fallbackCopyIsDisabled) {
        $this->queueAsyncFileCheck($originalUri);
      }
    }

    if (!$url) {
      $url = $this->getURLFromFirstAvailableAdapter($uri, $this->adapters);
    }

    if (!$url) {
      // TODO: Create a config option to shw log messages and re-enable this:
      /* $this->logger->alert('Streamy cannot generate a valid URL to serve the file with uri: %uri <br>CDN enabled: %cdn',
                           [
                             '%uri'      => $uri,
                             '%cdn'      => (bool) $this->cdnPlugin instanceof
                                            StreamyCDNBase,
//                             '%adapters' => print_r(array_values($this->adapters),
//                                                    TRUE),  // Generates overflow
                           ]);*/
    }

    return $url;
  }

  /**
   * Adds the current file request in a queue
   * to verify if it is present in the Master fileSystem.
   *
   * @param $uri
   * @return mixed
   */
  protected function queueAsyncFileCheck($uri) {
    $filePath = str_replace($this->schemeName . '://', '', $uri);

    $item = new \stdClass();
    $item->filePath = $filePath;
    $item->scheme = $this->schemeName;
    return $this->queueFactory->get('streamy_fallback_queue_worker')->createItem($item);
  }

  /**
   * @param $uri
   * @param $adapters
   * @return mixed
   */
  protected function getURLFromFirstAvailableAdapter($uri, $adapters) {
    foreach ($adapters as $level => $adapterInfo) {
      $streamyPluginName = $adapterInfo['name'];
      $adapter = $adapterInfo['adapter'];
      if ($this->streamyStreamManager->hasDefinition($streamyPluginName)) {
        $plugin = $this->streamyStreamManager->createInstance($streamyPluginName);
        $url = $plugin->getExternalUrl($uri, $this->schemeName, $level, $this->getReadOrWriteFilesystem('r'), $adapter);
        if ($url) {
          return $url;
        }
      }
    }
    return NULL;
  }

  /**
   * @param $path
   * @param $flags
   * @return bool
   */
  public function stat($path, $flags) {
    $args = [$this->buildFilesystemPath($path), $flags];
    return $this->invokeCall($this->getReadOrWriteFilesystem('r'), 'stat', $args);
  }

  /**
   * @param string $mode
   * @return \League\Flysystem\MountManager|null
   */
  protected function getReadOrWriteFilesystem($mode = 'w') {
    if ($mode == 'w') {
      return $this->writeFileSystem;
    }
    return $this->readFileSystem;
  }

  /**
   * @param string $path
   * @param string $schema
   * @return mixed
   */
  public function buildFilesystemPath($path, $schema = NULL) {
    // Makes sure that the path has a schema:://
    if ($path && strpos($path, $this->schemeName . '://') !== 0) {
      $path = $this->schemeName . '://' . $path;
    }
    return !$schema ? $path : str_replace($this->schemeName . '://', $schema . '://', $path);
  }

  /**
   * @param $path
   * @param $mode
   * @param $options
   * @return bool
   */
  public function createDir($path, $mode, $options) {
    $args = [$this->buildFilesystemPath($path), $mode, $options];
    $created = $this->invokeCall($this->getReadOrWriteFilesystem('w'), 'mkdir', $args);
    return $created;
  }

  /**
   * @param       $path
   * @param       $contents
   * @param array $config
   * @return bool
   */
  public function putStream($path, $contents, $config = []) {
    $args = [$this->buildFilesystemPath($path), $contents, $config];
    return $this->invokeCall($this->getReadOrWriteFilesystem('w'), 'putStream', $args);
  }

  /**
   * @param $path
   * @param $visibility
   * @return bool|mixed
   */
  public function setVisibility($path, $visibility) {
    try {
      $visibility = $this->getReadOrWriteFilesystem('w')->setVisibility($this->buildFilesystemPath($path), $visibility);//, 'setVisibility', $args);
      return $visibility;
    } catch (\LogicException $e) {
      // The adapter doesn't support visibility so we are going to fake the return.

    } catch (\Exception $e) {

      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $path
   * @return bool|mixed
   */
  public function touch($path) {
    return $this->invokeCall($this->getReadOrWriteFilesystem('w'), 'touch', [$path]);
  }

  /**
   * @param $path
   * @return bool|mixed
   */
  public function readStream($path) {
    //read to write
    $args = [$this->buildFilesystemPath($path), $path];
    return $this->invokeCall($this->getReadOrWriteFilesystem('r'), 'readStream', $args);
  }

  /**
   * @param $path
   * @return bool
   */
  public function deleteFile($path) {
    $args = [$this->buildFilesystemPath($path), $path];
    return $this->invokeCall($this->getReadOrWriteFilesystem('w'), 'delete', $args);
  }

  /**
   * @param $uri_from
   * @param $uri_to
   * @return bool
   */
  public function forcedRename($uri_from, $uri_to) {
    $args = [$uri_from, $uri_to];
    return $this->invokeCall($this->getReadOrWriteFilesystem('w'), 'forcedRename', $args);
  }

  /**
   * @param $path
   * @return bool|mixed
   */
  public function listContents($path) {
    $args = [$this->buildFilesystemPath($path)];
    return $this->invokeCall($this->getReadOrWriteFilesystem('r'), 'listContents', $args);
  }

  /**
   * @param $path
   * @return bool|mixed
   */
  public function has($path) {
    $args = [$this->buildFilesystemPath($path)];
    return $this->invokeCall($this->getReadOrWriteFilesystem('r'), 'has', $args);
  }

  /**
   * Removes a directory.
   *
   * @param string $uri
   * @param int    $options
   *
   * @return bool True on success, false on failure.
   */
  public function rmdir($uri, $options) {
    $args = [$this->buildFilesystemPath($uri), $options];
    return $this->invokeCall($this->getReadOrWriteFilesystem('w'), 'rmdir', $args);
  }

}
