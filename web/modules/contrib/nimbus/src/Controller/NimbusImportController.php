<?php

namespace Drupal\nimbus\Controller;

use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\nimbus\config\ProxyFileStorage;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Class NimbusImportController.
 *
 * @package Drupal\nimbus\Controller
 */
class NimbusImportController {

  /**
   * The config target.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $configTarget;
  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  private $configManager;

  /**
   * The config active.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $configActive;

  /**
   * NimbusExportController constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_target
   *   The target config storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   * @param \Drupal\Core\Config\StorageInterface $config_active
   *   The active config storage.
   */
  public function __construct(StorageInterface $config_target, ConfigManagerInterface $config_manager, StorageInterface $config_active) {
    $this->configTarget = $config_target;
    $this->configManager = $config_manager;
    $this->configActive = $config_active;
  }

  /**
   * The configuration import.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input object.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output object.
   *
   * @return bool
   *   Return false if something went wrong otherwise no return value.
   */
  public function configurationImport(InputInterface $input, OutputInterface $output) {
    $output->writeln('Overriden Import');

    $active_storage = \Drupal::service('config.storage');
    $source_storage = \Drupal::service('config.storage.staging');

    /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
    $config_manager = \Drupal::service('config.manager');
    $storage_comparer = new StorageComparer($source_storage, $active_storage, $config_manager);

    if (!$storage_comparer->createChangelist()->hasChanges()) {
      $output->writeln('There are no changes to import.');
      return TRUE;
    }

    $change_list = [];
    foreach ($storage_comparer->getAllCollectionNames() as $collection) {
      $change_list[$collection] = $storage_comparer->getChangelist(NULL, $collection);
    }
    $this->createTable($change_list, $output);
    $helper = new QuestionHelper();
    $question = new ConfirmationQuestion("Import the listed configuration changes? \n(y/n) ", !$input->isInteractive());

    if ($helper->ask($input, $output, $question)) {
      $config_importer = new ConfigImporter(
        $storage_comparer,
        \Drupal::service('event_dispatcher'),
        \Drupal::service('config.manager'),
        \Drupal::lock(),
        \Drupal::service('config.typed'),
        \Drupal::moduleHandler(),
        \Drupal::service('module_installer'),
        \Drupal::service('theme_handler'),
        \Drupal::service('string_translation')
      );

      if ($config_importer->alreadyImporting()) {
        $output->writeln('Another request may be synchronizing configuration already.');
        return FALSE;
      }
      try {
        $config_importer->import();
        $output->writeln('The configuration was imported successfully.');
      }
      catch (ConfigException $e) {
        $message = 'The import failed due for the following reasons:' . "\n";
        $message .= implode("\n", $config_importer->getErrors());
        watchdog_exception('config_import', $e);
        $output->writeln($message);
        return FALSE;
      }
    }
    else {
      $output->writeln('Aborted !');
      return FALSE;
    }
  }

  /**
   * Create a beautiful table.
   *
   * @param mixed $rows
   *   Rows array from the diff.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The symfony console output object.
   */
  protected function createTable($rows, OutputInterface $output) {
    $file_storage = \Drupal::service('config.storage.staging');
    if ($file_storage instanceof ProxyFileStorage) {
      $table = new Table($output);
      $elements = [];
      foreach ($rows as $collection => $row) {
        foreach ($row as $key => $config_names) {
          foreach ($config_names as $config_name) {
            $elements[] = [
              $collection,
              $config_name,
              $key,
              $file_storage->getFilePath($config_name),
            ];
          }
        }
      }
      $table
        ->setHeaders(['Collection', 'Config', 'Operation', 'Directory'])
        ->setRows($elements);
      $table->render();
    }
  }

}
