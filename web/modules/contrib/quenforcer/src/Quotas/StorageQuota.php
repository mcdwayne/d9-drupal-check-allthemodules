<?php

namespace Drupal\quenforcer\Quotas;

use Drupal\Core\Config\Config;

class StorageQuota extends Quota {

  const HUMAN_READABLE_NAME = 'Storage quota';
  const LIMIT_SETTING = 'storage_max_megabytes';
  const UNITS = 'MB';
  const FILES_SIZE_COMMAND = 'du -s -m -x ';

  protected $currently_used_database_amount;
  protected $currently_used_public_files_amount;
  protected $currently_used_private_files_amount;

  public function __construct(Config $config) {
    $this->currently_used_database_amount = $this->getDatabaseSizeInMegabytes();
    $this->currently_used_public_files_amount = $this->getFilesSizeInMegabytes('public');
    $this->currently_used_private_files_amount = $this->getFilesSizeInMegabytes('private');

    parent::__construct($config);
  }

  protected function calculateCurrentlyUsedAmount() {
    return $this->currently_used_database_amount +
           $this->currently_used_public_files_amount +
           $this->currently_used_private_files_amount;
  }

  public function exceededMessage() {
    return t('You have reached your storage quota limit of %limit %units preventing you from adding additional content. Please ask your administrator to increase it.', [
      '%limit' => $this->limit,
      '%units' => static::UNITS,
    ]);
  }

  /**
   * @see SiteAuditCheckDatabaseSize::calculateScore().
   */
  protected function getDatabaseSizeInMegabytes() {
    $database_name = \Drupal::database()->getConnectionOptions()['database'];

    $sql_query = 'SELECT SUM(TABLES.data_length + TABLES.index_length) / 1024 / 1024 ';
    $sql_query .= 'FROM information_schema.TABLES ';
    $sql_query .= 'WHERE TABLES.table_schema = :dbname ';
    $sql_query .= 'GROUP BY TABLES.table_schema ';

    return db_query($sql_query, [':dbname' => $database_name])->fetchField();
  }

  /**
   * @see SiteAuditCheckCodebaseSizeFiles::calculateScore().
   */
  protected function getFilesSizeInMegabytes($type = 'public') {
    // A DirectoryIterator::getSize() solution like http://stackoverflow.com/a/21409562/442022
    // would be more elegant, but it's slower than running this shell command.
    if ($path = \Drupal::service('file_system')->realpath($type . '://')) {
      $result = exec(static::FILES_SIZE_COMMAND . $path);
      return $this->getFilesSizeFromCommandResult($result);
    }
    return FALSE;
  }

  protected function getFilesSizeFromCommandResult($result) {
    return explode("\t", trim($result))[0];
  }

  protected function getReportDetails() {
    return [
      t('Database size: %size (%percent% of usage)', [
        '%size' => round($this->currently_used_database_amount) . ' ' . t(static::UNITS),
        '%percent' => $this->getComponentPercentage($this->currently_used_database_amount),
      ]),
      t('Public files: %size (%percent% of usage)', [
        '%size' => round($this->currently_used_public_files_amount) . ' ' . t(static::UNITS),
        '%percent' => $this->getComponentPercentage($this->currently_used_public_files_amount),
      ]),
      t('Private files: %size (%percent% of usage)', [
        '%size' => round($this->currently_used_private_files_amount) . ' ' . t(static::UNITS),
        '%percent' => $this->getComponentPercentage($this->currently_used_private_files_amount),
      ]),
    ];
  }

  protected function getComponentPercentage($component) {
    return round(($component / $this->currently_used_amount) * 100);
  }
}
