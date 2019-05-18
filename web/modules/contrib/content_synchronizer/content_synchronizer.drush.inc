<?php

/**
 * @file
 * Drush commands for content_synchronizer module.
 */

use Drupal\content_synchronizer\Entity\ImportEntity;
use Drupal\content_synchronizer\Processors\ExportEntityWriter;
use Drupal\content_synchronizer\Entity\ExportEntity;
use Drupal\content_synchronizer\Processors\ExportProcessor;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\content_synchronizer\Form\LaunchImportForm;

/**
 * Implementation of hook_drush_command().
 */
function content_synchronizer_drush_command() {
  $items = [];

  // Create Import.
  $items['content-synchronizer-create-import'] = [
    'description'        => 'Create an import from passed .zip file.',
    'arguments'          => [
      'file' => 'Optional. The cache bin to fetch from.',
    ],
    'required-arguments' => 1,
    'aliases'            => ['csci'],
  ];

  // Clean tmps.
  $items['content-synchronizer-clean-temporary-files'] = [
    'description' => 'Delete temporary files.',
    'aliases'     => ['csctf'],
  ];

  // Launch Export.
  $items['content-synchronizer-launch-export'] = [
    'description' => 'Launch the export of the passed ID.',
    'aliases'     => ['cslex'],
    'arguments'   => [
      'exportId' => 'The export id.',
    ],
  ];

  // Launch Import.
  $items['content-synchronizer-launch-import'] = [
    'description' => 'Launch the import of the passed ID.',
    'aliases'     => ['cslim'],
    'arguments'   => [
      'importId' => 'The import id.',
    ],
  ];

  return $items;
}

/**
 * Create an import entity from a zip file.
 *
 * @param string $path
 *   The path to the file to import.
 */
function drush_content_synchronizer_create_import($path) {
  if (file_exists($path)) {
    $extensionData = explode('.', $path);
    if (end($extensionData) == 'gz') {
      if ($file = file_save_data(file_get_contents($path))) {
        $name = strip_tags(t('Drush import - %date', [
          '%date' => \Drupal::service('date.formatter')
            ->format(time())
        ]));
        $ie = ImportEntity::create(
          [
            'name'                      => $name,
            ImportEntity::FIELD_ARCHIVE => $file
          ]
        );
        $ie->save();
        drush_log(t('The import has been created'), 'status');
      }
    }
    else {
      drush_log(t('The file is not a .gz archive'), 'error');
    }
  }
  else {
    drush_log(t('No file found'), 'error');
  }

}

/**
 * Delete temporary files.
 */
function drush_content_synchronizer_clean_temporary_files() {
  $path = \Drupal::service('file_system')
    ->realpath(ExportEntityWriter::GENERATOR_DIR);

  /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
  $fileSystem = \Drupal::service('file_system');
  foreach (glob($path . '/*') as $file) {
    if (is_dir($file)) {
      $fileSystem->deleteRecursive($file);
    }
  }
}

/**
 * Launche the specified export.
 */
function drush_content_synchronizer_launch_export($exportId) {

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
        $status = t('Exported');
      }
      catch (\Exception $error) {
        drush_log($error->getMessage(), 'error');
        $status = t('Error');
      }
      drush_log(t('[@key/@count] - "@label" - @status',
        [
          '@key'    => $key + 1,
          '@count'  => $count,
          '@label'  => ExportEntityWriter::getEntityLabel($entity),
          '@status' => $status,
        ]), 'status');
    }

    // Deplace archive.
    $tempArchive = $path = \Drupal::service('file_system')
      ->realpath($processor->closeProcess());
    $destination = './' . basename($tempArchive);

    rename($tempArchive, $destination);

    drush_log(t('Archive file : @destination', ['@destination' => $destination]));
  }
}

/**
 * Launche the specified import.
 */
function drush_content_synchronizer_launch_import($importId) {

  if ($import = ImportEntity::load($importId)) {
    if ($createType = drush_choice(LaunchImportForm::getCreateOptions(), LaunchImportForm::CREATION_ACTION_LABEL)) {
      if ($updateType = drush_choice(LaunchImportForm::getUpdateOptions(), LaunchImportForm::UPDATE_ACTION_LABEL)) {
        if ($doImport = drush_choice(['yes' => 'yes'], t('Proceed import ?'))) {
          $importProcessor = new ImportProcessor($import);
          $importProcessor->setCreationType($createType);
          $importProcessor->setUpdateType($updateType);

          // Loop for logs.
          $rootEntities = $import->getRootsEntities();
          $count = count($rootEntities);
          foreach ($rootEntities as $key => $rootEntityData) {
            try {
              $importProcessor->importEntityFromRootData($rootEntityData);
              $status = array_key_exists('edit_url', $rootEntityData) ? t('Updated') : t('Created');
            }
            catch (\Exception $error) {
              drush_log($error->getMessage(), 'error');
              $status = t('Error');
            }

            drush_log(t('[@key/@count] - "@label" - @status',
              [
                '@key'    => $key + 1,
                '@count'  => $count,
                '@status' => $status,
                '@label'  => $rootEntityData['label'],
              ]), 'status');
          }

          // Close process.
          $import->removeArchive();
        }
      }
    }
  }
}
