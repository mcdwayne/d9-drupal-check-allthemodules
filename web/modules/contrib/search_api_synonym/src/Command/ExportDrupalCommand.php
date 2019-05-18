<?php

namespace Drupal\search_api_synonym\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * Drupal Console Command for export synonyms.
 *
 * @package Drupal\search_api_synonym
 *
 * @DrupalCommand (
 *     extension="search_api_synonym",
 *     extensionType="module"
 * )
 */
class ExportDrupalCommand extends Command {

  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('searchapi:synonym:export')
      ->setDescription($this->trans('commands.searchapi.synonym.export.description'))
      ->addOption(
        'plugin',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.searchapi.synonym.export.options.plugin.description')
      )
      ->addOption(
        'langcode',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.searchapi.synonym.export.options.langcode.description')
      )
      ->addOption(
        'type',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.searchapi.synonym.export.options.type.description'),
        'all'
      )
      ->addOption(
        'filter',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.searchapi.synonym.export.options.filter.description'),
        'all'
      )
      ->addOption(
        'incremental',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.searchapi.synonym.export.options.incremental.description')
      )
      ->addOption(
        'file',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.searchapi.synonym.export.options.file.description')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Plugin manager
    $pluginManager = \Drupal::service('plugin.manager.search_api_synonym.export');

    // Options
    $plugin = $input->getOption('plugin');
    $langcode = $input->getOption('langcode');
    $type = $input->getOption('type');
    $filter = $input->getOption('filter');
    $file = $input->getOption('file');
    $incremental = $input->getOption('incremental');

    // Command output
    $io = new DrupalStyle($input, $output);

    // Validate option: plugin
    if (!$pluginManager->validatePlugin($plugin)) {
      $error = TRUE;
      $io->info($this->trans('commands.searchapi.synonym.export.messages.invalidplugin'));
    }

    // Validate option: langcode
    if (empty($langcode)) {
      $error = TRUE;
      $io->info($this->trans('commands.searchapi.synonym.export.messages.invalidlangcode'));
    }

    // Validate option: type
    if (!empty($type) && !$this->validateOptionType($type)) {
      $error = TRUE;
      $io->info($this->trans('commands.searchapi.synonym.export.messages.invalidtype'));
    }

    // Validate option: filter
    if (!empty($filter) && !$this->validateOptionFilter($filter)) {
      $error = TRUE;
      $io->info($this->trans('commands.searchapi.synonym.export.messages.invalidfilter'));
    }

    // Prepare export
    if (!isset($error)) {
      $io->info($this->trans('commands.searchapi.synonym.export.messages.start'));

      $options = [
        'langcode' => $langcode,
        'type' => $type,
        'filter' => $filter,
        'file' => $file,
        'incremental' => (int) $incremental,
      ];
      $pluginManager->setPluginId($plugin);
      $pluginManager->setExportOptions($options);

      // Execute export
      if ($result = $pluginManager->executeExport()) {

        // Output result
        $io->info($this->trans('commands.searchapi.synonym.export.messages.success'));
        $io->info($result);
      }
    }
  }

  /**
   * Validate that the type option is valid.
   *
   * @param string $type
   *   Type value from --type command option.
   *
   * @return boolean
   *   TRUE if valid, FALSE if invalid.
   */
  private function validateOptionType($type) {
    $types = ['synonym', 'spelling_error', 'all'];
    return in_array($type, $types);
  }

  /**
   * Validate that the filter option is valid.
   *
   * @param string $filter
   *   Type value from --filter command option.
   *
   * @return boolean
   *   TRUE if valid, FALSE if invalid.
   */
  private function validateOptionFilter($filter) {
    $filters = ['nospace', 'onlyspace', 'all'];
    return in_array($filter, $filters);
  }

}
