<?php

namespace Drupal\interface_string_stats;

use Drupal\locale\StringStorageInterface;
use Drupal\Core\Database\Connection;

/**
 * Process strings for use in usage stats.
 */
class StringRequestProcessor {

  /**
   * The locale storage.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $stringDatabase;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new StringRequestProcessor class.
   *
   * @param \Drupal\locale\StringStorageInterface $string_storage
   *   The string storage.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(StringStorageInterface $string_storage, Connection $database) {
    $this->stringDatabase = $string_storage;
    $this->database = $database;
  }

  /**
   * Adds a string to the database for usage stats.
   *
   * @param string $lang_code
   *   The language code.
   * @param string $string
   *   The untranslated string.
   * @param string $context
   *   The context of the string.
   *
   * @return bool
   *   TRUE if the string was saved.
   *
   * @throws \Exception
   */
  public function processStringRequest($lang_code, $string, $context) {
    $translation = $this->stringDatabase->findTranslation([
      'language' => $lang_code,
      'source' => $string,
      'context' => $context,
      'read_only' => TRUE,
    ]);

    if ($translation && $translation->lid) {
      $this->database->insert('interface_string_stats')
        ->fields([
          'lid' => $translation->lid,
        ])
        ->execute();
    }
    else {
      return FALSE;
    }

    return TRUE;
  }

}
