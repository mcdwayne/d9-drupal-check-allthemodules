<?php

namespace Drupal\tome_sync\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\tome_sync\ContentHasherInterface;
use Drupal\tome_sync\ImporterInterface;
use Drupal\tome_sync\TomeSyncHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Contains the tome:import-partial command.
 *
 * @internal
 */
class ImportPartialCommand extends ImportCommand {

  /**
   * The content hasher.
   *
   * @var \Drupal\tome_sync\ContentHasherInterface
   */
  protected $contentHasher;

  /**
   * Constructs an ImportPartialCommand instance.
   *
   * @param \Drupal\tome_sync\ImporterInterface $importer
   *   The importer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\tome_sync\ContentHasherInterface $content_hasher
   *   The content hasher.
   */
  public function __construct(ImporterInterface $importer, EntityTypeManagerInterface $entity_type_manager, ContentHasherInterface $content_hasher) {
    parent::__construct($importer, $entity_type_manager);
    $this->contentHasher = $content_hasher;
  }

  /**
   * {@inheritdoc}
   */
  protected  function configure() {
    $this->setName('tome:import-partial')
      ->setDescription('Imports only changed config, content, and files.')
      ->addOption('process-count', NULL, InputOption::VALUE_OPTIONAL, 'Limits the number of processes to run concurrently.', self::PROCESS_COUNT)
      ->addOption('entity-count', NULL, InputOption::VALUE_OPTIONAL, 'The number of entities to export per process.', self::ENTITY_COUNT)
      ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Assume "yes" as answer to all prompts,');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    if (!$this->contentHasher->hashesExist()) {
      $this->io()->error('No content hashes exist to perform a partial import. Please run a full Tome install and export (i.e. "drush tome:install && drush tome:export"), which will ensure hashes exist in the database and filesystem.');
      return 1;
    }

    $options = $input->getOptions();
    $change_list = $this->contentHasher->getChangelist();

    if (empty($change_list['modified']) && empty($change_list['deleted']) && empty($change_list['added'])) {
      $this->io()->success('No content has been changed or deleted.');
    }

    if (!empty($change_list['modified'])) {
      $this->io()->section('Modified');
      $this->io()->listing($change_list['modified']);
    }

    if (!empty($change_list['added'])) {
      $this->io()->section('Added');
      $this->io()->listing($change_list['added']);
    }

    if (!empty($change_list['deleted'])) {
      $this->io()->section('Deleted');
      $this->io()->listing($change_list['deleted']);
    }

    if (!$options['yes'] && !$this->io()->confirm('Your local site\'s config, content, and files will be deleted or updated.', FALSE)) {
      return 0;
    }

    foreach ($change_list['deleted'] as $content_name) {
      list($entity_type_id, $uuid, $langcode) = TomeSyncHelper::getPartsFromContentName($content_name);
      $results = $this->entityTypeManager->getStorage($entity_type_id)->loadByProperties([
        'uuid' => $uuid,
      ]);
      if (count($results) === 1) {
        $entity = reset($results);
        if ($entity instanceof TranslatableInterface && $langcode) {
          $translation = $entity->getTranslation($langcode);
          if ($translation && !$translation->isDefaultTranslation()) {
            $entity->removeTranslation($langcode);
            $entity->save();
          }
          else {
            $entity->delete();
          }
        }
        else {
          $entity->delete();
        }
      }
    }

    $this->prepareConfigForImport();
    if (!$this->runCommand($this->executable . " config:import --partial -y", NULL, NULL)) {
      return 1;
    }

    $chunked_names = $this->importer->getChunkedNames();
    foreach ($chunked_names as $i => $chunk) {
      foreach ($chunk as $j => $name) {
        if (!in_array($name, $change_list['modified'], TRUE) && !in_array($name, $change_list['added'], TRUE)) {
          unset($chunked_names[$i][$j]);
        }
      }
      $chunked_names[$i] = array_values($chunked_names[$i]);
    }

    if (!$this->importChunks($chunked_names, $options['entity-count'], $options['process-count'])) {
      return 1;
    }

    $this->importer->importFiles();

    if (!$this->runCommand($this->executable . " tome:import-complete")) {
      return 1;
    }

    $this->io()->success('Imported config, content, and files.');
  }

}
