<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "managed_files",
 *  label = @Translation("Managed Files"),
 *  description = "Checks on managed files and the upload directory.",
 *  tags = {
 *   "content",
 *  }
 * )
 */
class ManagedFiles extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSrv;

  /**
   * CacheBackend constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $database, $file_srv) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->database = $database;
    $this->fileSrv = $file_srv;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('database'),
      $container->get('file_system')
    );
  }

  public function getFindings() {
    $findings = [];

    $managed_file_size = $this->getManagedFileSize();
    $real_file_size = $this->getFileDirectorySize();

    $finding_data = [
      'real' => $real_file_size,
      'managed' => $managed_file_size,
    ];

    if ($real_file_size == 0) {
      $findings[] = $this->notPerformed('managed_files', $finding_data);
    }
    elseif ($real_file_size - $managed_file_size < 0) {
      $findings[] = $this->actionRequested('managed_files', $finding_data);
    }
    elseif ($real_file_size - $managed_file_size > ($managed_file_size * 0.3)) {
      $findings[] = $this->needsReview('managed_files', $finding_data);
    }
    else {
      $findings[] = $this->noActionRequired('managed_files', $finding_data);
    }

    return $findings;
  }

  /**
   * Gets the managed file size according to the database.
   *
   * @return int
   *   The managed file size in bytes.
   */
  protected function getManagedFileSize() {
    // Get the default file scheme.
    $default_file_scheme = file_default_scheme() . "://";

    if ($this->database->schema()->tableExists('file_managed')) {

      // Query the managed file table.
      $query = $this->database->select('file_managed', 'fm');

      // Sum the filesize column.
      $query->addExpression('SUM(filesize)', 'total_size');

      // But only for the default file scheme.
      $like = $this->database->escapeLike($default_file_scheme) . '%';
      $query->condition('URI', $like, 'LIKE');

      // Get the result.
      $result = $query->execute()->fetchField();
    }

    return empty($result) ? 0 : $result;
  }


  /**
   * @return int
   *   The size of the file directory
   */
  protected function getFileDirectorySize() {
    // Get the default file scheme.
    $default_file_scheme = file_default_scheme() . "://";

    // Get the real path to the file directory.
    $file_dir_path = $this->fileSrv->realpath($default_file_scheme);

    // If the path is a directory, run `du` against the directory.
    if (is_dir($file_dir_path)) {
      exec("du -s $file_dir_path | cut -f1", $output);

      if (!empty($output)) {
        $kb = reset($output);

        // By default, du returns in kb, so we need to expand it to bytes.
        return $kb * 1024;
      }
    }

    return 0;
  }

  /**
   * Format an integer of a size in bytes as a human readable format.
   *
   * @param int $bytes
   *   The size in bytes.
   * @param int $precision
   *   The precision of the rounding operation. Default: 2.
   *
   * @return string
   *   The size in a human readable format.
   */
  protected function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    return round($bytes, $precision) . ' ' . $units[$pow];
  }
}
