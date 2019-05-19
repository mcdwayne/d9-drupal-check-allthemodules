<?php

namespace Drupal\browscap;

use Drupal\Core\Database\Database;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Class BrowscapImporter.
 *
 * @package Drupal\browscap
 */
class BrowscapImporter {

  use StringTranslationTrait;

  const BROWSCAP_IMPORT_OK = 1;
  const BROWSCAP_IMPORT_VERSION_ERROR = -1;
  const BROWSCAP_IMPORT_NO_NEW_VERSION = -2;
  const BROWSCAP_IMPORT_DATA_ERROR = -3;

  /**
   * Config Factory Interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * A cache backend interface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Client constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config Factory Interface.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend instance to use.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config, CacheBackendInterface $cache, TranslationInterface $string_translation) {
    $this->config = $config;
    $this->cache = $cache;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Helper function to update the browscap data.
   *
   * @param BrowscapEndpoint $browscap
   *   The endpoint service for Browscap.
   * @param bool $cron
   *   Optional import environment. If false, display status messages to the
   *   user in addition to logging information with the watchdog.
   *
   * @return int
   *   A code indicating the result:
   *   - BROWSCAP_IMPORT_OK: New data was imported.
   *   - BROWSCAP_IMPORT_NO_NEW_VERSION: No new data version was available.
   *   - BROWSCAP_IMPORT_VERSION_ERROR: Checking the current data version
   *                                    failed.
   *   - BROWSCAP_IMPORT_DATA_ERROR: The data could not be downloaded or parsed.
   */
  public function import(BrowscapEndpoint $browscap, $cron = TRUE) {

    $config = $this->config->getEditable('browscap.settings');

    /*
     * Check if there is a new version
     */
    $local_version = $config->get('version');
    \Drupal::logger('browscap')->notice('Checking for new browscap version...');
    $current_version = $browscap->getVersion();

    // Was it an error?
    if ($current_version == BrowscapImporter::BROWSCAP_IMPORT_VERSION_ERROR) {
      // Display a message to the user if the update process was triggered
      // manually.
      if ($cron == FALSE) {
        drupal_set_message($this->t("Couldn't check version."), 'error');
      }
      return BrowscapImporter::BROWSCAP_IMPORT_VERSION_ERROR;
    }

    // Compare the current and local version numbers to determine if the
    // Browscap data requires updating.
    if ($current_version == $local_version) {
      // Log a message with the watchdog.
      \Drupal::logger('browscap')->info('No new version of browscap to import');
      // Display a message to user if the update process was triggered manually.
      if ($cron == FALSE) {
        drupal_set_message($this->t('No new version of browscap to import'));
      }
      return BrowscapImporter::BROWSCAP_IMPORT_NO_NEW_VERSION;
    }

    /*
     * If there is a new version retrieve the new data
     */
    $browscap_data = $browscap->getBrowscapData($cron);

    // Process the browscap data.
    $result = $this->processData($browscap_data);
    // If it's not an array, it's an error.
    if ($result != static::BROWSCAP_IMPORT_OK) {
      return $result;
    }

    // Clear the browscap data cache.
    $this->cache->invalidateAll();

    // Update the browscap version and imported time.
    $config->set('version', $current_version)
      ->set('imported', REQUEST_TIME)
      ->save();

    // Log a message with the watchdog.
    \Drupal::logger('browscap')->notice('New version of browscap imported: %version', ['%version' => $current_version]);

    // Display a message to user if the update process was triggered manually.
    if ($cron == FALSE) {
      drupal_set_message($this->t('New version of browscap imported: %version', ['%version' => $current_version]));
    }

    return static::BROWSCAP_IMPORT_OK;
  }

  /**
   * Processes Browscap data.
   *
   * The purpose of this function is to perform the queries on the {browscap}
   * table as a transaction. This vastly improves performance with database
   * engines such as InnoDB and ensures that queries will work while new data
   * is being imported.
   *
   * @param string $browscap_data
   *   Unparsed Browscap data.
   *
   * @return int
   *   A code indicating the result:
   *   - BROWSCAP_IMPORT_OK: New data was imported.
   *   - BROWSCAP_IMPORT_DATA_ERROR: The data could not be downloaded or parsed.
   */
  private function processData(&$browscap_data) {
    // Start a transaction. The variable is unused. That's on purpose.
    $transaction = Database::getConnection()->startTransaction();

    // Delete all data from database.
    Database::getConnection()->delete('browscap')->execute();

    // Skip the header division.
    $header_division = $this->getNextIniDivision($browscap_data);
    // Assert that header division less than length of entire INI string.
    if (strlen($header_division) >= strlen($browscap_data)) {
      return static::BROWSCAP_IMPORT_DATA_ERROR;
    }

    // Skip the version division.
    $version_divison = $this->getNextIniDivision($browscap_data);
    // Assert that Version section in division string.
    if (strpos($version_divison, "Browscap Version") === FALSE) {
      return static::BROWSCAP_IMPORT_DATA_ERROR;
    }

    // Get default properties division.
    // Assumption: The default properties division is the third division.
    $default_properties_division = $this->getNextIniDivision($browscap_data);
    // Assert that DefaultProperties section in division string.
    if (strpos($default_properties_division, "[DefaultProperties]") === FALSE) {
      return static::BROWSCAP_IMPORT_DATA_ERROR;
    }

    // Parse and save remaining divisions.
    while ($division = $this->getNextIniDivision($browscap_data)) {
      // The division is concatenated with the default properties division
      // because each division has at least one section that inherits properties
      // from the default properties section.
      $divisions = $default_properties_division . $division;
      $parsed_divisions = $this->parseData($divisions);
      if (!$parsed_divisions) {
        // There was an error parsing the data.
        return static::BROWSCAP_IMPORT_DATA_ERROR;
      }
      $this->saveParsedData($parsed_divisions);
    }
    return static::BROWSCAP_IMPORT_OK;
  }

  /**
   * Parses Browscap data.
   *
   * @param string $browscap_data
   *   Portion of Browscap INI.
   *
   * @return array
   *   Parsed Browscap data.
   */
  private function parseData(&$browscap_data) {

    // Replace 'true' and 'false' with '1' and '0'.
    $browscap_data = preg_replace(
      [
        '/=\s*"?true"?\s*$/m',
        '/=\s*"?false"?\s*$/m',
      ],
      [
        "=1",
        "=0",
      ],
      $browscap_data
    );

    // Parse the browscap data as a string.
    $browscap_data = parse_ini_string($browscap_data, TRUE, INI_SCANNER_RAW);

    return $browscap_data;
  }

  /**
   * Saves parsed Browscap data.
   *
   * @param array $browscap_data
   *   Browscap data that has been parsed with parse_ini_string() or
   *   parse_ini_file().
   */
  private function saveParsedData(array &$browscap_data) {
    // Prepare the data for insertion.
    $import_data = [];
    foreach ($browscap_data as $key => $values) {
      // Store the current value.
      $original_values = $values;

      // Replace '*?' with '%_'.
      $user_agent = strtr($key, '*?', '%_');

      // Remove trailing spaces to prevent "duplicate entry" errors. Databases
      // such as MySQL do not preserve trailing spaces when storing VARCHARs.
      $user_agent = rtrim($user_agent);

      // Change all array keys to lowercase.
      $original_values = array_change_key_case($original_values);

      // Add to array of data to import.
      $import_data[$user_agent] = $original_values;

      // Remove processed data to reduce memory usage.
      unset($browscap_data[$key]);
    }

    $query = db_insert('browscap')->fields(['useragent', 'data']);
    foreach ($import_data as $user_agent => $values) {
      // Recurse through the available user agent information.
      $previous_parent = NULL;
      $parent = isset($values['parent']) ? $values['parent'] : FALSE;
      while ($parent && $parent !== $previous_parent) {
        $parent_values = isset($import_data[$parent]) ? $import_data[$parent] : [];
        $values = array_merge($parent_values, $values);
        $previous_parent = $parent;
        $parent = isset($parent_values['parent']) ? $parent_values['parent'] : FALSE;
      }

      // Do not import DefaultProperties user agent.
      // It is currently only needed for inheriting properties prior to import.
      if ($user_agent == 'DefaultProperties') {
        continue;
      }

      $query->values([
        'useragent' => $user_agent,
        'data' => serialize($values),
      ]);
    }
    $query->execute();
  }

  /**
   * Gets next division of Browscap INI.
   *
   * @param string $ini
   *   Browscap INI.
   *
   * @return string
   *   Next division of Browscap INI.
   */
  private function getNextIniDivision(&$ini) {
    static $offset = 0;
    $division_begin = $offset;
    $division_end = $this->findIniDivisionEnd($ini, $division_begin);
    $division_length = $division_end - $division_begin;
    $division = substr($ini, $division_begin, $division_length);
    $offset += $division_length;
    return $division;
  }

  /**
   * Finds the offset of the end of the INI division.
   *
   * @param string $ini
   *   Browscap INI.
   * @param int $division_begin
   *   Offset of the beginning of the INI division.
   *
   * @return int
   *   Offset of the end of the INI division.
   */
  private function findIniDivisionEnd(&$ini, $division_begin) {
    $header_prefix = ';;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;';

    // Start search from one character after offset so the header at the
    // beginning of the part is not matched.
    $offset = $division_begin + 1;

    $division_end = strpos($ini, $header_prefix, $offset);
    // When the beginning of the next division cannot be found, the end of the
    // INI string has been reached.
    if ($division_end === FALSE) {
      $division_end = strlen($ini) - 1;
    }

    return $division_end;
  }

}
