<?php
// $Id$
/**
 * @file
 * Oracle specific driver that takes advantage of oracles native XML support
 *
 * In order to take advantage of XML support the following XML
 *
 */
namespace Drupal\forena\FrxPlugin\Driver;
use Drupal\forena\FrxAPI;
use Drupal\forena\Token\SQLReplacer;
use Drupal\forena\File\DataFileSystem;

/**
 * Class FrxMSSQL
 * @FrxDriver(
 *   id="FrxMSSQL",
 *   name="Microsoft SQL Server Driver"
 * )
 */
class FrxMSSQL extends DriverBase {

  use FrxAPI; 
  private $db;
  private $use_mssql_xml;

  /**
   * Object constructor
   *
   * @param string $uri 
   *   Database connection string.
   * @param string $repos_path 
   *   Path to location of data block definitions
   */
  public function __construct($name, $conf, DataFileSystem $fileSystem) {

    parent::__construct($name, $conf, $fileSystem);
    $this->db_type = 'mssql';
    $this->use_mssql_xml = FALSE;
    $uri = $conf['uri'];
    $this->debug = $conf['debug'];
    if ($conf['mssql_xml']) $this->use_mssql_xml = TRUE;
    if ($uri) {
      // Test for mssql suport
      if (!is_callable('mssql_connect')) {
        $this->app()->error('MSSQL support not installed.', 'MSSQL mssql support not installed.');
        return NULL; 
      }
      try {
        ini_set('mssql.textlimit', 2147483647);
        ini_set('mssql.textsize', 2147483647);
        $db = mssql_connect($uri, $conf['user'], $conf['password']);
        $this->db = $db;

        if ($db) {
          mssql_select_db($conf['database'], $db);
          mssql_query("SET QUOTED_IDENTIFIER ON");
        }
      } catch (\Exception $e) {
        $this->app()->error('Unable to connect to database ' . $conf['title'], $e->getMessage());
      }

    }
    else {
      $this->app()->error('No database connection string specified', 'No database connection: ' . print_r($conf, 1));
    }

    // Set up the stuff required to translate.
    $this->te = new SQLReplacer($this);
  }
  /**
   * Get data based on file data block in the repository.
   *
   * @param string $sql
   *   Query to execute
   * @param array $options
   *   Key/value pair or array containing parameter type ifnormation for the 
   *   query.
   * @return \SimpleXMLElement | array
   *   Data from executed sql query. 
   */
  public function sqlData($sql, $options = array()) {
    // Load the block from the file

    $db = $this->db;

    // Load the types array based on data
    $this->types = isset($options['type']) ? $options['type'] : array();

    $xml ='';
    // Load the types array based on data
    $this->types = isset($options['type']) ? $options['type'] : array();
    if ($sql && $db) {
      $sql = $this->te->replace($sql);

      if ($this->use_mssql_xml) {
        $xml = $this->mssql_xml($sql, 'table');
      }
      else {
        $xml = $this->php_xml($sql);
      }
      if ($this->debug) {
        if ($xml) $d = htmlspecialchars($xml->asXML());
        $this->debug('SQL: ' . $sql, '<pre> SQL:' . $sql . "\n XML: " . $d . "\n</pre>");
      }
      return $xml;
    }
    else {
      return NULL; 
    }

  }

  /**
   * Generate xml from sql using the provided f_forena
   *
   * @param string $sql
   *   SQL statement
   * @return \SimpleXMLElement
   *   XML Element
   */
  private function mssql_xml($sql, $block) {
    $db = $this->db;

    //$rs->debugDumpParams();
    $fsql = $sql . ' FOR XML AUTO';
    $rs = mssql_query($db, $fsql, array($sql, ''));
    if ($rs) {
      $row = mssql_fetch_row($rs);
      $xml_text = $row[0];
    }
    if ($xml_text) {
      $xml = new \SimpleXMLElement($xml_text);
      if ($xml->getName() == 'error') {
        $msg = (string)$xml . ' in ' . $block . '.sql. ';
        $this->app()->error($msg . 'See logs for more info', $msg . ' in <pre> ' . $sql . '</pre>');
      }
    }
    if ($rs) mssql_free_result($rs);
    return $xml;
  }

  private function php_xml($sql) {
    $db = $this->db;
    $xml = new \SimpleXMLElement('<table/>');

    $rs = mssql_query($sql, $db);
    $rownum = 0;
    while ($row = mssql_fetch_assoc($rs)) {
      $rownum++;
      $row_node = $xml->addChild('row');
      $row_node['num'] = $rownum;
      foreach ($row as $key => $value) {
        $row_node->addChild(strtolower($key), htmlspecialchars($value));
      }
    }
    if ($rs) mssql_free_result($rs);
    return $xml;

  }

  /**
   * Perform search of tables.
   * @see FrxDataSource::searchTables()
   */
  public function searchTables($str) {
    $str .= '%';
    $db = $this->db;
    $sql = $this->searchTablesSQL();
    $str = "'" . str_replace("'", "''", $str) . "'";
    $sql = str_replace(':str', $str, $sql);
    $rownum = 0;
    $rs = mssql_query($sql, $db);
    $tables = array();
    while ($row = mssql_fetch_assoc($rs)) {
      $tables[] = $row['table_name'];
    }
    if ($rs) mssql_free_result($rs);
    return $tables;

  }

  /**
   * Perform search of tables.
   * @see FrxDataSource::searchTables()
   */
  public function searchTableColumns($table, $str) {
    $str .= '%';
    $db = $this->db;
    $sql = $this->searchTableColumnsSQL();
    $str = "'" . str_replace("'", "''", $str) . "'";
    $sql = str_replace(':str', $str, $sql);

    $table = "'" . str_replace("'", "''", $table) . "'";
    $sql = str_replace(':table', $table, $sql);
    $rownum = 0;

    $rs = mssql_query($sql, $db);
    $columns = array();
    if ($rs) while ($row = mssql_fetch_assoc($rs)) {
      $columns[] = $row['COLUMN_NAME'];
    }
    if ($rs) mssql_free_result($rs);
    return $columns;

  }

  /**
   * Implement custom SQL formatter to make sure that strings are properly escaped.
   * Ideally we'd replace this with something that handles prepared statements, but it
   * wouldn't work for
   *
   * @param string $value
   *   The value of the string replacement. 
   * @param string $key
   *   The token name being replaced. 
   * @param bool $raw
   *   True implies that data shold not be formatted. 
   * @return string 
   *   Formatted data
   */
  public function format($value, $key, $raw = FALSE) {
    if ($raw) return $value;
    $value = $this->parmConvert($key, $value);
    if ($value===''||$value===NULL) {
      $value = 'NULL';
    }
    elseif (is_int($value)) {
      $value = (int)$value;
      $value = (string)$value;
    }
    elseif (is_float($value)) {
      $value = (float)$value;
      $value = (string)$value;
    }
    else $value = "'" . str_replace("'", "''", $value) . "'";
    return $value;
  }

  /**
   * Destructor - Closes database connections.
   *
   */
  public function __destruct() {
    $db = $this->db;
    if ($db) {
      mssql_close($db);
    }
  }
}