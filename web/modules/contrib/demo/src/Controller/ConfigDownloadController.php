<?php

namespace Drupal\demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Diff\DiffFormatter;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\system\FileDownloadController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for config module routes.
 */
class ConfigDownloadController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The target storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $targetStorage;

  /**
   * The source storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The file download controller.
   *
   * @var \Drupal\system\FileDownloadController
   */
  protected $fileDownloadController;

  /**
   * The diff formatter.
   *
   * @var \Drupal\Core\Diff\DiffFormatter
   */
  protected $diffFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage'),
      $container->get('config.storage.sync'),
      $container->get('config.manager'),
      new FileDownloadController(),
      $container->get('diff.formatter')
      );
  }

  /**
   * Constructs a ConfigController object.
   *
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source storage.
   * @param \Drupal\system\FileDownloadController $file_download_controller
   *   The file download controller.
   */
  public function __construct(StorageInterface $target_storage, StorageInterface $source_storage, ConfigManagerInterface $config_manager, FileDownloadController $file_download_controller, DiffFormatter $diff_formatter) {
    $this->targetStorage = $target_storage;
    $this->sourceStorage = $source_storage;
    $this->configManager = $config_manager;
    $this->fileDownloadController = $file_download_controller;
    $this->diffFormatter = $diff_formatter;
  }

  /**
   * Downloads a tarball of the site configuration.
   */
  public function downloadExport() {
    $fileconfig = 'private://' . \Drupal::config('demo.settings')->get('demo_dump_path', 'demo');
    $request = \Drupal::request();
    $date_string = date("Y-m-d-H-i");
    $hostname = str_replace('.', '-', $request->getHttpHost());
    $filename = '/config' . '-' . $hostname . '-' . $date_string . '.tar.gz';

    $archiver = new ArchiveTar($fileconfig . $filename, 'gz');
    // Get raw configuration data without overrides.
    foreach ($this->configManager->getConfigFactory()->listAll() as $name) {
      $archiver->addString("$name.yml", Yaml::encode($this->configManager->getConfigFactory()->get($name)->getRawData()));
    }
    // Get all override data from the remaining collections.
    foreach ($this->targetStorage->getAllCollectionNames() as $collection) {
      $collection_storage = $this->targetStorage->createCollection($collection);
      foreach ($collection_storage->listAll() as $name) {
        $archiver->addString(str_replace('.', '/', $collection) . "/$name.yml", Yaml::encode($collection_storage->read($name)));
      }
    }
    $request = new Request(['file' => $filename]);
    if (!file_prepare_directory($fileconfig, FILE_CREATE_DIRECTORY)) {
      return FALSE;
    }
    // ----------------------------------------------------------------------.
    $scheme = 'private';
    $target = $request->query->get('file');
    // Merge remaining path arguments into relative file path.
    $uri = $scheme . '://demo' . $target;
    if (file_stream_wrapper_valid_scheme($scheme)) {
      // Let other modules provide headers and controls access to the file.
      $headers = $this->moduleHandler()->invoke('demo', 'file_download', [$uri]);
      foreach ($headers as $result) {
        if ($result == -1) {
          throw new AccessDeniedHttpException();
        }
      }
      if (count($headers)) {
        // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
        // sets response as not cacheable if the Cache-Control header is not
        // already modified. We pass in FALSE for non-private schemes for the
        // $public parameter to make sure we don't change the headers.
        drupal_set_message(t('Snapshot has been created.'));
        return $this->redirect('demo.manage_config');
      }
      throw new AccessDeniedHttpException();
    }
    throw new NotFoundHttpException();
    // ----------------------------------------------------------------------.
  }

  /**
   * Shows diff of specified configuration file.
   *
   * @param string $source_name
   *   The name of the configuration file.
   * @param string $target_name
   *   (optional) The name of the target configuration file if different from
   *   the $source_name.
   * @param string $collection
   *   (optional) The configuration collection name. Defaults to the default
   *   collection.
   *
   * @return string
   *   Table showing a two-way diff between the active and staged configuration.
   */
  public function diff($source_name, $target_name = NULL, $collection = NULL) {
    if (!isset($collection)) {
      $collection = StorageInterface::DEFAULT_COLLECTION;
    }
    $diff = $this->configManager->diff($this->targetStorage, $this->sourceStorage, $source_name, $target_name, $collection);
    $this->diffFormatter->show_header = FALSE;
    $build = [];
    $build['#title'] = t('View changes of @config_file', ['@config_file' => $source_name]);
    // Add the CSS for the inline diff.
    $build['#attached']['library'][] = 'system/diff';
    $build['diff'] = [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['diff'],
      ],
      '#header' => [
      ['data' => t('Active'), 'colspan' => '2'],
      ['data' => t('Staged'), 'colspan' => '2'],
      ],
      '#rows' => $this->diffFormatter->format($diff),
    ];
    $build['back'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => [
          'dialog-cancel',
        ],
      ],
      '#title' => "Back to 'Synchronize configuration' page.",
      '#url' => Url::fromRoute('config.sync'),
    ];
    return $build;
  }
}
