<?php

namespace Drupal\automatic_updates\ReadinessChecker;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use DrupalFinder\DrupalFinder;

/**
 * File ownership checker.
 */
class FileOwnership extends Filesystem {
  use StringTranslationTrait;

  /**
   * FileOwnership constructor.
   *
   * @param \DrupalFinder\DrupalFinder $drupal_finder
   *   The Drupal finder.
   */
  public function __construct(DrupalFinder $drupal_finder) {
    $this->drupalFinder = $drupal_finder;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCheck() {
    $file_path = $this->getRootPath() . '/core/core.api.php';
    return $this->ownerIsScriptUser($file_path);
  }

  /**
   * Check if file is owned by the same user as which is running the script.
   *
   * Helps identify scenarios when the check is run by web user and the files
   * are owned by a non-web user.
   *
   * @param string $file_path
   *   The file path to check.
   *
   * @return array
   *   An array of translatable strings if there are file ownership issues.
   */
  protected function ownerIsScriptUser($file_path) {
    $messages = [];
    if (function_exists('posix_getuid')) {
      $file_owner_uid = fileowner($file_path);
      $script_uid = posix_getuid();
      if ($file_owner_uid !== $script_uid) {
        $messages[] = $this->t('Files are owned by uid "@owner" but PHP is running as uid "@actual". The file owner and PHP user should be the same during an update.', [
          '@owner' => $file_owner_uid,
          '@file' => $file_path,
          '@actual' => $script_uid,
        ]);
      }
    }
    return $messages;
  }

}
