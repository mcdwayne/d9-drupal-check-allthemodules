<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

use BackupMigrate\Core\File\BackupFileReadableInterface;
use BackupMigrate\Core\Plugin\FileProcessorTrait;
use BackupMigrate\Core\Plugin\PluginBase;

/**
 * Rename filter for database table backup.
 *
 * Renames all database tables to/from a generic name, so that they can be
 * saved or restored in a particular test, to/from a generic file.
 *
 * Configuration:
 * - source_prefix: Prefix used in the source (usually the test's database
 *   prefix).
 * - destination_prefix: Prefix used in the destination (a generic prefix
 *   that you specify).
 */
class DBTableRenameFilter extends PluginBase {

  use FileProcessorTrait;

  /**
   * Replaces source_prefix with destination_prefix.
   *
   * @param BackupFileReadableInterface $file
   *   Database backup file.
   *
   * @return BackupFileReadableInterface
   *   Modified backup file.
   */
  public function afterBackup(BackupFileReadableInterface $file) {
    $source = $this->confGet('source_prefix');
    $destination = $this->confGet('destination_prefix');
    return $this->doReplace($file, $source, $destination, TRUE);
  }

  /**
   * Replaces destination_prefix with source_prefix.
   *
   * @param BackupFileReadableInterface $file
   *   Database backup file.
   *
   * @return BackupFileReadableInterface
   *   Modified backup file.
   */
  public function beforeRestore(BackupFileReadableInterface $file) {
    $source = $this->confGet('source_prefix');
    $destination = $this->confGet('destination_prefix');
    return $this->doReplace($file, $destination, $source, FALSE);
  }

  /**
   * Replaces text in a file.
   *
   * @param BackupFileReadableInterface $file
   *   Backup file to replace text in.
   * @param string $search
   *   String to search for.
   * @param string $replace
   *   String to replace it with.
   * @param string $rename
   *   If TRUE, rename the file after the operation to the previous file name.
   *
   * @return BackupFileReadableInterface
   *   Modified or new file, with $search replaced with $replace.
   */
  protected function doReplace(BackupFileReadableInterface $file, $search, $replace, $rename) {
    $contents = $file->readAll();
    $count = 0;
    $new_contents = str_replace($search, $replace, $contents, $count);

    if (!$count) {
      // No replacements made.
      return $file;
    }

    // Replacements were made, so make a new file and return it.
    $new_file = $this->getTempFileManager()->create('mysql');
    $new_file->write($new_contents);
    $new_file->close();
    if ($rename) {
      $new_file->setName($file->getName());
    }

    return $new_file;
  }

}
