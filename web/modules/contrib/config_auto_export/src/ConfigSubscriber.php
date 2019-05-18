<?php

namespace Drupal\config_auto_export;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\DestructableInterface;
use Drupal\Core\Serialization\Yaml;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Config subscriber.
 */
class ConfigSubscriber implements EventSubscriberInterface, DestructableInterface {

  /** @var \Drupal\Core\Config\ImmutableConfig */
  protected $config;

  /** @var \Drupal\Core\Config\CachedStorage */
  protected $configCache;

  /** @var \Drupal\Core\Config\FileStorage */
  protected $configStorage;

  /** @var array */
  protected $configSplitFiles;

  /** @var bool */
  protected $active = TRUE;

  /** @var bool */
  protected $triggerNeeded = FALSE;

  /**
   * Constructs a new Settings object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    CachedStorage $config_cache,
    FileStorage $config_storage
  ) {
    $this->config = $config_factory->get('config_auto_export.settings');
    $this->configCache = $config_cache;
    $this->configStorage = $config_storage;
  }

  /**
   * @return bool
   *   Protected function enabled.
   */
  protected function enabled() {
    static $enabled;

    if (!isset($enabled)) {
      $enabled = FALSE;
      if ($this->config->get('enabled')) {
        $uri = $this->config->get('directory');
        if (file_prepare_directory($uri, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
          if (is_writable($uri) || @chmod($uri, 0777)) {
            $enabled = TRUE;
          }
        }
      }
    }

    return $enabled;
  }

  /**
   * Trigger the webhook.
   */
  protected function trigger() {
    $webhook = $this->config->get('webhook');
    if (empty($webhook)) {
      return;
    }
    $exportPath = \Drupal::service('file_system')->realpath($this->config->get('directory'));
    $configPath = \Drupal::service('file_system')->realpath(config_get_config_directory(CONFIG_SYNC_DIRECTORY));
    $data = [
      'form_params' => Yaml::decode(str_replace(
        ['[export directory]', '[config directory]'],
        [$exportPath, $configPath],
        $this->config->get('webhook_params'))),
    ];

    try {
      $client = new Client(['base_uri' => $webhook]);
      $client->request('post', '', $data);
    }
    catch (\Exception $ex) {
      \Drupal::logger('config')->critical('Trigger for config auto export failed: {msg}', ['msg' => $ex->getMessage()]);
    }
  }

  /**
   * Read all config files from config splits, if available.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function readConfigSplitFiles() {
    $this->configSplitFiles = [];
    if (!\Drupal::moduleHandler()->moduleExists('config_split')) {
      return;
    }
    $extension = '.yml';
    $regex = '/' . str_replace('.', '\.', $extension) . '$/';
    foreach (\Drupal::entityTypeManager()->getStorage('config_split')->loadMultiple() as $split) {
      $this->configSplitFiles += file_scan_directory($split->get('folder'), $regex, ['key' => 'filename']);
    }
    ksort($this->configSplitFiles);
  }

  /**
   * @param string $name
   *
   * @return bool
   */
  protected function existsInConfigSplit($name) {
    if (!isset($this->configSplitFiles)) {
      try {
        $this->readConfigSplitFiles();
      } catch (InvalidPluginDefinitionException $e) {
      } catch (PluginNotFoundException $e) {
      }
    }
    return isset($this->configSplitFiles[$name . '.yml']);
  }

  /**
   * Saves changed config to a configurable directory.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Public function onConfigSave event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    if ($this->active && $this->enabled()) {
      $name = $event->getConfig()->getName();
      if ($this->existsInConfigSplit($name)) {
        return;
      }
      $this->configStorage->write($name, $this->configCache->read($name));
      $this->triggerNeeded = TRUE;
    }
  }

  /**
   * Turn off this subscriber on importing configuration.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   Public function onConfigImportValidate event.
   */
  public function onConfigImportValidate(ConfigImporterEvent $event) {
    $this->active = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onConfigSave', 0];
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImportValidate', 1024];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function destruct() {
    if ($this->triggerNeeded) {
      $this->trigger();
    }
  }

}
