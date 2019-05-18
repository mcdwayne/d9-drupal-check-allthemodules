<?php

/**
 * Class responsible for communication with DB.
 */
class InstapageCmsPluginDBModel {

  /**
   * @var object Class instance.
   */
  private static $dbModel = null;

  /**
   * @var string DB prefix.
   */
  public $prefix = null;

  /**
   * @var string Charset collatation.
   */
  public $charsetCollate = null;

  /**
   * @var string Options table name.
   */
  public $optionsTable = null;

  /**
   * @var string Pages table name.
   */
  public $pagesTable = null;

  /**
   * @var Debug log table name.
   */
  public $debugTable = null;

  /**
   * Class constructor. Sets the properties based on current CMS settings.
   */
  function __construct() {
    $this->prefix = InstapageCmsPluginConnector::getSelectedConnector()->getDBPrefix();
    $this->charsetCollate = InstapageCmsPluginConnector::getSelectedConnector()->getCharsetCollate();
    $this->optionsTable = $this->prefix . 'instapage_options';
    $this->pagesTable = $this->prefix . 'instapage_pages';
    $this->debugTable = $this->prefix . 'instapage_debug';
  }

  /**
   * Gets the class instance.
   *
   * @return object Class instance.
   */
  public static function getInstance() {
    if (self::$dbModel === null) {
      self::$dbModel = new InstapageCmsPluginDBModel();
    }

    return self::$dbModel;
  }

  /**
   * Executes a SQL query.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   * @return bool True if the query is successful. DB error is logged and false if returned otherwise.
   */
  public function query($sql) {
    $args = func_get_args();
    array_shift($args);

    if (isset($args[0]) && is_array($args[0]) ) {
      $args = $args[0];
    }

    return InstapageCmsPluginConnector::getSelectedConnector()->query($sql, $args);
  }

  /**
   * Gets the last ID of an insert query.
   *
   * @return integer|boolean Last insert ID of false on error.
   */
  public function lastInsertId() {
    return InstapageCmsPluginConnector::getSelectedConnector()->lastInsertId();
  }

  /**
   * Executes the query and returns the first row.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   * @return mixed first row of results of the query.
   */
  public function getRow($sql) {
    $args = func_get_args();
    array_shift($args);

    if (isset($args[0]) && is_array($args[0]) ) {
      $args = $args[0];
    }

    return InstapageCmsPluginConnector::getSelectedConnector()->getRow($sql, $args);
  }

  /**
   * Executes the query and returns a list of results.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   * @return mixed first row of results of the query.
   */
  public static function getResults($sql) {
    $args = func_get_args();
    array_shift($args);

    if (isset($args[0]) && is_array($args[0]) ) {
      $args = $args[0];
    }

    return InstapageCmsPluginConnector::getSelectedConnector()->getResults($sql, $args);
  }

  /**
   * Initiates Instapage plugin's DB structure.
   */
  public function initPluginTables() {
    $this->initOptionsTable();
    $this->initPagesTable();
    $this->initDebugTable();
    $this->updateDB();
  }

  /**
   * Initiates Instapage plugin's DB structure for options table.
   */
  private function initOptionsTable() {
    $sql = sprintf('SHOW TABLES LIKE \'%s\'', $this->optionsTable);
    $result = $this->getRow($sql);

    if ($result) {
      return true;
    }

    $sql = sprintf('CREATE TABLE IF NOT EXISTS %s(' .
    'id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, ' .
    'plugin_hash VARCHAR(255) DEFAULT \'\', ' .
    'api_keys TEXT NULL, ' .
    'user_name VARCHAR(255) DEFAULT \'\', ' .
    'config TEXT NULL, ' .
    'metadata TEXT NULL, ' .
    'UNIQUE KEY id (id)) %s', $this->optionsTable, $this->charsetCollate);

    $this->query($sql);
  }

  /**
   * Initiates Instapage plugin's DB structure for pages table.
   */
  private function initPagesTable() {
    $sql = sprintf('SHOW TABLES LIKE \'%s\'', $this->pagesTable);
    $result = $this->getRow($sql);

    if ($result) {
      return true;
    }

    $sql = sprintf('CREATE TABLE IF NOT EXISTS %s(' .
    'id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, ' .
    'instapage_id INT UNSIGNED NOT NULL, ' .
    'slug VARCHAR(255) DEFAULT \'\' NOT NULL, ' .
    'type VARCHAR(4) DEFAULT \'page\' NOT NULL, ' .
    'time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, ' .
    'stats_cache TEXT NULL, ' .
    'stats_cache_expires INT UNSIGNED, ' .
    'enterprise_url VARCHAR(255) DEFAULT \'\' NOT NULL, ' .
    'UNIQUE KEY id (id)) %s', $this->pagesTable, $this->charsetCollate );

    $this->query($sql);
  }

  /**
   * Initiates Instapage plugin's DB structure for debug table.
   */
  private function initDebugTable() {
    $sql = sprintf('SHOW TABLES LIKE \'%s\'', $this->debugTable);
    $result = $this->getRow($sql);

    if ($result) {
      return true;
    }

    $sql = sprintf('CREATE TABLE IF NOT EXISTS %s(' .
    'id MEDIUMINT(9) UNSIGNED NOT NULL AUTO_INCREMENT, ' .
    'time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, ' .
    'text TEXT NULL, ' .
    'caller VARCHAR(255) DEFAULT \'\' NOT NULL, ' .
    'name VARCHAR(255) DEFAULT \'\' NOT NULL, ' .
    'UNIQUE KEY id (id)) %s', $this->debugTable, $this->charsetCollate );

    $this->query($sql);
  }

  /**
   * Check current DB structure version and updates it if necessary.
   */
  private function updateDB() {
    $db_version = intval(InstapageCmsPluginHelper::getMetadata('db_version', 0), 10);

    if ($db_version < 300000010) {
      InstapageCmsPluginHelper::writeDiagnostics($db_version, 'Current db version. Doing update.');
      $sql = 'SHOW COLUMNS FROM ' . $this->optionsTable . ' LIKE %s';
      $metadata_exists = $this->getRow($sql, 'metadata');
      $sql = 'SHOW COLUMNS FROM ' . $this->pagesTable . ' LIKE %s';
      $enterprise_url_exists = $this->getRow($sql, 'enterprise_url');

      if (!$metadata_exists) {
        $sql = sprintf('ALTER TABLE %s ADD metadata TEXT NULL', $this->optionsTable);
        $this->query($sql);
      }

      if (!$enterprise_url_exists) {
        $sql = sprintf('ALTER TABLE %s ADD enterprise_url VARCHAR(255) DEFAULT \'\' NOT NULL', $this->pagesTable);
        $this->query($sql);
      }

      $sql = sprintf('ALTER TABLE %s MODIFY api_keys TEXT NULL', $this->optionsTable);
      $this->query($sql);
      $sql = sprintf('ALTER TABLE %s MODIFY config TEXT NULL', $this->optionsTable);
      $this->query($sql);
      $sql = sprintf('ALTER TABLE %s MODIFY slug VARCHAR(255) DEFAULT \'\' NOT NULL', $this->pagesTable);
      $this->query($sql);
      $sql = sprintf('ALTER TABLE %s MODIFY time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL', $this->pagesTable);
      $this->query($sql);
      $sql = sprintf('ALTER TABLE %s MODIFY time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL', $this->debugTable);
      $this->query($sql);

      InstapageCmsPluginHelper::updateMetadata('db_version', 300000010);
    }
  }

  /**
   * Initiates Instapage plugin's DB tables.
   */
  public function removePluginTables() {
    $this->query('DROP TABLE IF EXISTS ' . $this->optionsTable);
    $this->query('DROP TABLE IF EXISTS ' . $this->pagesTable);
    $this->query('DROP TABLE IF EXISTS ' . $this->debugTable);
  }
}
