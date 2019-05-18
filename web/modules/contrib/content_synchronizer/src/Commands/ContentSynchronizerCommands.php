<?php

namespace Drupal\content_synchronizer\Commands;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\File\FileSystem;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;
use Drupal\content_synchronizer\Entity\ImportEntity;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Entity\ExportEntity;
use Drupal\content_synchronizer\Processors\ExportProcessor;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\content_synchronizer\Form\LaunchImportForm;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ContentSynchronizerCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * File System.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * ContentSynchronizerCommands constructor.
   */
  public function __construct(DateFormatter $dateFormatter, FileSystem $fileSystem) {
    $this->dateFormatter = $dateFormatter;
    $this->fileSystem = $fileSystem;
  }

  /**
   * Create an import from passed .zip file.
   *
   * @param string $path
   *   Optional. The cache bin to fetch from.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option as_function
   *   if this command will call as a function, in this case, return
   *   ImportEntity Id.
   *
   * @command content:synchronizer-create-import
   * @aliases csci,content-synchronizer-create-import
   *
   * @return int
   *   The import id.
   */
  public function synchronizerCreateImport($path, array $options = ['as_function' => FALSE]) {
    if (file_exists($path)) {
      $extensionData = explode('.', $path);
      if (end($extensionData) == 'gz') {
        if ($file = file_save_data(file_get_contents($path))) {
          $name = strip_tags($this->t('Drush import - %date', [
            '%date' => $this->dateFormatter->format(time())
          ]));
          $ie = ImportEntity::create(
            [
              'name'                      => $name,
              ImportEntity::FIELD_ARCHIVE => $file
            ]
          );
          $ie->save();
          $this->logger->notice($this->t('The import has been created')
            ->__toString());
        }
      }
      else {
        $this->logger->error($this->t('The file is not a .zip archive')
          ->__toString());
      }
    }
    else {
      $this->logger->error($this->t('No file found')->__toString());
    }

    if ($options['as_function'] == TRUE) {
      return $ie->id();
    }
  }

  /**
   * Delete temporary files.
   *
   * @command content:synchronizer-clean-temporary-files
   * @aliases csctf,content-synchronizer-clean-temporary-files
   */
  public function synchronizerCleanTemporaryFiles() {
    $path = $this->fileSystem->realpath(ExportEntityWriter::GENERATOR_DIR);
    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $fileSystem = \Drupal::service('file_system');
    foreach (glob($path . '/*') as $file) {
      if (is_dir($file)) {
        $fileSystem->deleteRecursive($file);
      }
    }
  }

  /**
   * Launch the export of the passed ID.
   *
   * @param int $exportId
   *   The export id.
   * @param string $destination
   *   File to create.
   *
   * @command content:synchronizer-launch-export
   * @aliases cslex,content-synchronizer-launch-export
   */
  public function synchronizerLaunchExport($exportId, $destination = '') {
    if ($export = ExportEntity::load($exportId)) {

      $entitiesToExport = $export->getEntitiesList();
      $writer = new ExportEntityWriter();
      $writer->initFromId($export->label());
      $processor = new ExportProcessor($writer);

      // Loop for log.
      $count = count($entitiesToExport);
      foreach (array_values($entitiesToExport) as $key => $entity) {
        try {
          $processor->exportEntity($entity);
          $status = $this->t('Exported');
        }
        catch (\Exception $error) {
          $this->logger->error($error->getMessage());
          $status = $this->t('Error');
        }
        $this->logger->notice($this->t('[@key/@count] - "@label" - @status',
          [
            '@key'    => $key + 1,
            '@count'  => $count,
            '@label'  => ExportEntityWriter::getEntityLabel($entity),
            '@status' => $status,
          ])->__toString());
      }

      // Deplace archive.
      $tempArchive = $path = $this->fileSystem->realpath($processor->closeProcess());
      if ($destination == '') {
        $destination = './' . basename($tempArchive);
      }

      rename($tempArchive, $destination);

      $this->logger->notice($this->t('Archive file : @destination', ['@destination' => $destination])
        ->__toString());
    }
  }

  /**
   * Launch the import of the passed ID.
   *
   * @param int $importId
   *   The import id.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option publish
   *   Autopublish imported content :  publish|unpublish
   * @option update
   *   Update stategy :  systematic|if_recent|no_update
   *
   * @command content:synchronizer-launch-import
   * @aliases cslim,content-synchronizer-launch-import
   *
   * @throws \Exception
   */
  public function synchronizerLaunchImport($importId, array $options = [
    'publish' => '',
    'update'  => ''
  ]) {
    if ($import = ImportEntity::load($importId)) {

      if (!in_array('publication_' . $options['publish'], array_keys(LaunchImportForm::getCreateOptions()))) {
        $message = "Publish option must be in : publish|unpublish";
        throw new \Exception($message);
      }
      if (!in_array('update_' . $options['update'], array_keys(LaunchImportForm::getUpdateOptions()))) {
        $message = "Update option must be in : systematic|if_recent|no_update";
        throw new \Exception($message);
      }

      $createType = 'publication_' . $options['publish'];
      $updateType = 'update_' . $options['update'];
      $importProcessor = new ImportProcessor($import);
      $importProcessor->setCreationType($createType);
      $importProcessor->setUpdateType($updateType);

      // Loop for logs.
      $rootEntities = $import->getRootsEntities();
      $count = count($rootEntities);
      foreach ($rootEntities as $key => $rootEntityData) {
        try {
          $importProcessor->importEntityFromRootData($rootEntityData);
          $status = array_key_exists('edit_url', $rootEntityData) ? $this->t('Updated') : $this->t('Created');
        }
        catch (\Exception $error) {
          $this->logger->error($error->getMessage());
          $status = $this->t('Error');
        }

        $this->logger->notice($this->t('[@key/@count] - "@label" - @status',
          [
            '@key'    => $key + 1,
            '@count'  => $count,
            '@status' => $status,
            '@label'  => $rootEntityData['label'],
          ])->__toString());
      }

      // Close process.
      $import->removeArchive();
    }

  }

  /**
   * Export all : bind together create Export, attach all node in & cslex.
   *
   * @param string $destination
   *   Destination file.
   *
   * @command content:synchronizer-export-all
   * @aliases csexall
   */
  public function synchronizerAllExport($destination = '') {

    // 1 : create export.
    $exportEntity = ExportEntity::create(['name' => 'export-all']);
    $exportEntity->save();
    $exportId = $exportEntity->id();

    // 2 : add all nodes / taxo.
    foreach (['node', 'taxonomy_term'] as $entity_type) {
      $ids = \Drupal::entityQuery($entity_type)->execute();
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->loadMultiple($ids);
      foreach ($entities as $entity) {
        $exportEntity->addEntity($entity);
      }
    }

    // 3 : make export.
    $this->synchronizerLaunchExport($exportId, $destination);
  }

  /**
   * Import from zip : bind together csci & cslim.
   *
   * @param string $file_path
   *   Zip file path.
   *
   * @command content:synchronizer-import-zip
   * @aliases csimzip
   *
   * @throws \Exception
   */
  public function synchronizerImportZip($file_path) {

    // 1 : create export.
    $importId = $this->synchronizerCreateImport($file_path, ['as_function' => TRUE]);

    // 2 : add all nodes of all node-types.
    $this->synchronizerLaunchImport($importId, [
      'publish' => 'publish',
      'update'  => 'systematic'
    ]);
  }

}
