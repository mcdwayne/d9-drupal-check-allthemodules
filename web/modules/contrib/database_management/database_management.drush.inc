<?php

/**
 * @file
 * Drush commands for the database_management module.
 */

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Drupal\Core\Site\Settings;
use Drupal\Core\Database\Database;

/**
 * Implements hook_drush_command().
 */
function database_management_drush_command() {
  $items = [];

  $items['database-export'] = [
    'description' => 'Exports and uploads the current database.',
    'options' => [
      'version' => 'Manually set a version for the backup.',
    ],
    'aliases' => ['db-export', 'dbe'],
    'examples' => [
      'drush database-export --version=drupal-db' => 'Exports the database to drupal-db.sql.gz',
      'drush database-export' => 'Exports the database to 2017-10-19.sql.gz',
    ],
  ];

  $items['database-import'] = [
    'description' => 'Downloads and imports most recent database backup.',
    'options' => [
      'version' => 'Database version to import.',
    ],
    'aliases' => ['db-import', 'dbi'],
    'examples' => [
      'drush database-import --version=2017-10-19' => 'Downloads and imports the database backup from October 19th, 2017.',
      'drush database-import' => 'Downloads and imports the most recent database backup.',
    ],
  ];

  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 *
 * Exports and uploads the current default database.
 */
function drush_database_management_database_export() {
  $config = \Drupal::config('database_management.settings');

  // Database connection information, derived from active settings.php file.
  $db_info = Database::getConnectionInfo('default');
  $db_username = $db_info['default']['username'];
  $db_password = $db_info['default']['password'];
  $db_name = $db_info['default']['database'];

  /** @var Aws\S3\S3Client $s3 */
  $s3 = new S3Client([
    'credentials' => [
      'key' => Settings::get('aws_s3_key'),
      'secret' => Settings::get('aws_s3_secret'),
    ],
    'region' => $config->get('s3_region', 'us-east-1'),
    'version' => 'latest',
    'http' => [
      'progress' => function ($dl_total_size, $dl_size_so_far, $ul_total_size, $ul_size_so_far) {
        drush_database_management_progress($ul_total_size, $ul_size_so_far);
      },
    ],
  ]);

  // Default version to todays date unless user specifies a version.
  $version = drush_get_option('version', \Drupal::service('date.formatter')->format(time(), 'html_date'));
  $file_name = sprintf('%s%s.sql', $config->get('s3_file_prefix', ''), $version);


  // Export gzipped database to specified file.
  drush_set_option('gzip', TRUE);
  drush_set_option('result-file', "/tmp/$file_name");
  drush_sql_dump();

  // Upload backup file to S3.
  $s3_path = sprintf('%s%s/%s.gz', $config->get('s3_folder_prefix', ''), $version, $file_name);
  drush_print(dt('Uploading backup /tmp/@file_name.gz to s3://@s3_bucket:@s3_path', [
    '@file_name' => $file_name,
    '@s3_bucket' => $config->get('s3_bucket'),
    '@s3_path' => $s3_path,
  ]));
  try {
    $s3->putObject([
      'Bucket' => $config->get('s3_bucket'),
      'Key' => $s3_path,
      'SourceFile' => "/tmp/$file_name.gz",
    ]);
  }
  catch (S3Exception $e) {
    return drush_set_error(dt('Error uploading file: @error', ['@error' => $e->getMessage()]));
  }

  drush_print('Database export complete.');
}

/**
 * Implements drush_hook_COMMAND().
 *
 * Downloads and imports a database backup.
 */
function drush_database_management_database_import() {
  $config = \Drupal::config('database_management.settings');

  // Database connection information, derived from active settings.php file.
  $db_info = Database::getConnectionInfo('default');
  $db_username = $db_info['default']['username'];
  $db_password = $db_info['default']['password'];
  $db_name = $db_info['default']['database'];

  /** @var Aws\S3\S3Client $s3 */
  $s3 = new S3Client([
    'credentials' => [
      'key' => Settings::get('aws_s3_key'),
      'secret' => Settings::get('aws_s3_secret'),
    ],
    'region' => $config->get('s3_region', 'us-east-1'),
    'version' => 'latest',
    'http' => [
      'progress' => function ($dl_total_size, $dl_size_so_far, $ul_total_size, $ul_size_so_far) {
        drush_database_management_progress($dl_total_size, $dl_size_so_far);
      },
    ],
  ]);

  $version = drush_get_option('version', \Drupal::service('date.formatter')->format(time(), 'html_date'));
  $file_name = sprintf('%s%s.sql.gz', $config->get('s3_file_prefix', ''), $version);

  // Check to see if backup has been downloaded already. If not, download
  // from S3.
  if (!file_exists(sprintf('/tmp/%s', $file_name))) {
    drush_print(dt('Downloading backup @file_name', ['@file_name' => $file_name]));
    try {
      $s3->getObject([
        'Bucket' => $config->get('s3_bucket'),
        'Key' => sprintf('%s%s/%s', $config->get('s3_folder_prefix', ''), $version, $file_name),
        'SaveAs' => sprintf('/tmp/%s', $file_name),
      ]);
    }
    // If there's an error downloading, notify the user and remove the
    // partially downloaded file.
    catch (S3Exception $e) {
      // If Error is 'Specified Key doesn't exist', update message to be more
      // reader friendly. Like 'Requested file doesn't exist'.
      unlink(sprintf('/tmp/%s', $file_name));
      return drush_set_error(dt('Error downloading file: @error', ['@error' => $e->getAwsErrorMessage()]));
    }
  }

  /** @var Drush\Sql\SqlBase $sql */
  $sql = drush_sql_get_class();
  if ($sql->db_exists()) {
    if (!drush_confirm(dt('\'@db_name\' database already exists. Drop all tables and import backup?', ['@db_name' => $db_name]))) {
      return drush_user_abort();
    }
    $sql->drop($sql->listTables());
  }
  else {
    drush_print(dt('Database \'@db_name\' doesn\'t exist - creating it now.', ['@db_name' => $db_name]));
    $sql->createdb();
  }

  drush_print(dt('Importing backup @file_name into database', ['@file_name' => $file_name]));
  exec(sprintf('zcat < /tmp/%s | mysql -u %s -p%s %s', $file_name, $db_username, $db_password, $db_name));
  drush_print('Database import complete. Be sure to clear cache before attempting to use Drupal.');
}

/**
 * Helper function to display a progress bar for the database import function.
 *
 * @param int $total
 *   The total number of bytes to download.
 * @param int $part
 *   How many bytes have been downloaded so far.
 */
function drush_database_management_progress($total, $part) {
  static $bar = NULL;
  static $completed = FALSE;

  if (!$completed) {
    if ($bar === NULL) {
      /** @var Drupal\drush_progress\Drush\ProgressBar $bar */
      $bar = drush_progress_bar(FALSE);
    }
    if ($part !== 0) {
      $bar->setProgress($total, $part);
      if ($total == $part) {
        $bar->end();
        $completed = TRUE;
        $bar = NULL;
      }
    }
  }
}
