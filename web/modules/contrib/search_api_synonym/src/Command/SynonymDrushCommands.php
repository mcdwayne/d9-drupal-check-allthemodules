<?php

/**
 * @file
 * Drush commands for the Search API Synonym module.
 */

namespace Drupal\search_api_synonym\Command;

use Drush\Commands\DrushCommands;
use Drupal\search_api_synonym\Export\ExportPluginManager;
use Exception;

/**
 * Drush commands for the Search API Synonym module.
 *
 * @package Drupal\search_api_synonym\Command
 */
class SynonymDrushCommands extends DrushCommands {

  /**
   * @var \Drupal\search_api_synonym\Export\ExportPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs the Drush command.
   *
   * @param \Drupal\search_api_synonym\Export\ExportPluginManager $pluginManager
   */
  public function __construct(ExportPluginManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * Export search synonyms to a specific format.
   *
   * @command search-api-synonym:export
   * @option plugin Machine name of the export plugin. E.g. solr.
   * @option langcode Language being exported. Use the language code. E.g. en or da.
   * @option type Synonym type. Allowed values: synonym = Synomyms, spelling_error = Spelling errors, all = All types (synonyms and spelling errors). Defaults to "alL".
   * @option filter Export filter. Allowed values: nospace = Skip all words containing a space, onlyspace = Skip all words without a space. Defaults to "all".
   * @option incremental Incremental export - use Unix timestamp. Only export synonyms changed after the provided timestamp.
   * @option file File name used when saving the exported file. Include extension but not folder name!.
   * @aliases sapi-syn:export, sapi-syn-ex
   * @validate-module-enabled search_api_synonym
   * @usage drush search-api-synonym:export --plugin=solr langcode=da type=spelling_error filter=all
   *   Export all Danish spelling errors in the Solr format.
   * @usage sapi-syn:export --plugin=solr langcode=da type=spelling_error filter=all
   *   Export all Danish spelling errors in the Solr format.
   */
  public function export($options = ['plugin' => '', 'langcode' => '', 'type' => 'all', 'filter' => 'all', 'incremental' => 0, 'file' => '']) {
    $error = FALSE;

    // Validate option: plugin
    if (!$this->pluginManager->validatePlugin($options['plugin'])) {
      $error = TRUE;
      throw new \Exception('--plugin is not valid. Please, use an existing plugin machine name.');
    }

    // Validate option: langcode
    if (empty($options['langcode'])) {
      $error = TRUE;
      throw new \Exception('--langcode is not valid. Please, use an existing language code.');
    }

    // Validate option: type
    if (!$this->validateOptionType($options['type'])) {
      $error = TRUE;
      throw new \Exception('--type option is not valid. The only allowed values are "synonym", "spelling_error", "all"');
    }

    // Validate option: filter
    if (!$this->validateOptionFilter($options['filter'])) {
      $error = TRUE;
      throw new \Exception('--filter option is not valid. The only allowed values are "nospace", "onlyspace", "all"');
    }

    // Prepare export
    if (!$error) {
      $this->output()->writeln(dt('Starting synonym export....'));

      $options['incremental'] = (int) $options['incremental'];

      $this->pluginManager->setPluginId($options['plugin']);
      $this->pluginManager->setExportOptions($options);

      // Execute export
      if ($result = $this->pluginManager->executeExport()) {

        // Output result
        $this->output()->writeln(dt('Synonyms export and saved in the following file:'));
        $this->output()->writeln($result);
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
