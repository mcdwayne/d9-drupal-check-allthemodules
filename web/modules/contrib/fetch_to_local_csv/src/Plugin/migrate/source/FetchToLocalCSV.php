<?php

namespace Drupal\fetch_to_local_csv\Plugin\migrate\source;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_source_csv\Plugin\migrate\source;

/**
 * Extension of CSV to allow fetch of remote CSV, useful for when you specify a header line,
 * since CSV uses rewind to read the header line and rewind doesn't work on remote files.
 * 
 *
 * @MigrateSource(
 *   id = "fetchtolocalcsv"
 * )
 */
class FetchToLocalCSV extends \Drupal\migrate_source_csv\Plugin\migrate\source\CSV {
    public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration){
        parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
        if (empty($this->configuration['remote'])) {
            throw new MigrateException('You must declare the "remote" to the source CSV file in your source settings.');
        }
        system_retrieve_file($this->configuration['remote'],$this->configuration['path'] , FALSE, TRUE);
    }
}