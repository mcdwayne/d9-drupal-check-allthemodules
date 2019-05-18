<?php
/**
 * @file
 * General database engine used to do sql queries.
 *
 */
namespace Drupal\forena\FrxPlugin\Driver;
use Drupal\forena\Token\SQLReplacer;
use Drupal\forena\File\DataFileSystem;
use \SimpleXMLElement;

/**
 * Class FrxPDO
 * @FrxDriver(
 *   id="FrxPDO",
 *   name="PDO Driver"
 * )
 */
class FrxPDO extends DriverBase {


  private $db;
  public $debug;


  /**
   * Object constructor
   *
   * @param string $name
   *   PDO data connection name
   * @param array $conf
   *   Array containing configuration data. 
   */
  public function __construct($name, $conf, DataFileSystem $fileSystem) {
    parent::__construct($name, $conf, $fileSystem);
    $uri = $conf['uri'];
    $this->debug = @$conf['debug'];
    if ($uri) {
      // Test for PDO suport
      if (!class_exists('PDO')) {
        $this->app()->error('PDO support not installed.', 'PDO support not installed.');
        return NULL; 
      }

      $options = array();
      if (@$conf['mysql_charset']) {
        $options = array(
        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $conf['mysql_charset'],
        );
      }

      // Test for driver support
      @list($prot, $c) = explode(':', $uri, 2);
      $drivers = \PDO::getAvailableDrivers();
      $this->db_type = $prot;

      if ($drivers && (array_search($prot, $drivers)===FALSE)) {
        $msg = 'PDO driver support for ' . $prot . ' not installed';
        $this->app()->error($msg, $msg);
        return NULL; 
      }
      try {
        if (isset($conf['user'])) {
          $db = new \PDO($uri, $conf['user'], @$conf['password'], $options);
        }
        else {
          $db = new \PDO($uri, NULL, NULL, $options);
        }
        $this->db = $db;
        if (!is_object($db)) {
          $this->app()->error('Unknown error connecting to database ' . $uri);
        }
      } catch (\PDOException $e) {
        $this->app()->error('Unable to connect to database', $e->getMessage());
      }
      if($this->db) $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

    }
    else {
      $this->app()->error('No database connection string specified');
    }

    // Set up the stuff required to translate.
    $this->te = new SQLReplacer($this);
  }

  public function parseConnectionStr() {
    $uri = @$this->conf['uri'];
    @list($prot, $conn) = explode(':', $uri, 2);
    $conn = str_replace(';', ' ', $conn);
    $info = array();
    foreach(explode(' ', $conn) as $pairs) {
      if (strpos($pairs, '=')!==FALSE) {
        list($key, $value) = @explode('=', $pairs, 2);
        $info[trim($key)] = trim($value);
      }
    }
    return $info;
  }

  /**
   * Get data based on file data block in the repository.
   *
   * @param string $sql 
   *   Query to execute
   * @param array $options
   *   Array of parameter types for the query.
   * @return SimpleXMLElement | array 
   *   Data from executed SQL query. 
   */
  public function sqlData($sql, $options = array()) {
    // Load the block from the file
    $db = $this->db;
    $xml ='';
    // Load the types array based on data
    $this->types = isset($options['type']) ? $options['type'] : array();

    if ($sql && $db) {
      $sql = $this->te->replace($sql);
      try {
        $rs = $db->query($sql);

      }
      catch ( \PDOException $e) {
        $line = $e->getLine();
        $text = $e->getMessage();
       $this->app()->error('PDO_error: $line', $text);
       return NULL;

      }
      if (@$options['return_type'] == 'raw') {
        return $rs;
      }
      $xml = new \SimpleXMLElement('<table/>');
      $e = $db->errorCode();

      if ($e != '00000') {
        $i = $db->errorInfo();
        $text =  $i[0] . ':' . $i[2];
        //if (forena_user_access_check('build forena sql blocks')) {
        if (!$this->block_name) {
          $short = t('%e', array('%e' => $text));
        } else {
          $short = t('SQL Error in %b.sql', array('%b' => $this->block_name));
        }
        $this->app()->error($short, $text);

      }
      else if ($rs && $rs->columnCount())  {
        if (@$options['return_type'] == 'raw') return $rs;
        $rownum = 0;
        foreach ($rs as $data) {
          $rownum++;
          $row_node = $xml->addChild('row');
          $row_node['num'] = $rownum;
          foreach ($data as $key => $value) {
            $row_node->addChild($key, htmlspecialchars($value));
          }
        }
      }

      if ($this->debug) {
        $d = '';
        if ($xml)  {
          $d = htmlspecialchars($xml->asXML());
        }
        $this->app()->debug('SQL: ' . $sql, '<pre> SQL:' . $sql . "\n XML: " . $d . "/n</pre>");
      }
      return $xml;
    }
    else {
      return NULL; 
    }

  }

  /**
   * Wrapper method cause some ODBC providers do not support
   * quoting.   We're going to assume the MSSQL method of quoting.
   * @param string $value
   *   Value to be quoted. 
   * @return string 
   *   Properly quoted value. 
   */
  public function quote($value) {
    $new_value =  $this->db->quote($value);
    if (($value!=='' || $value!==NULL) && !$new_value) {
      $value = "'" . str_replace("'", "''", $value) . "'";
    }
    else {
      $value = $new_value;
    }
    return $value;
  }

  /**
   * Implement custom SQL formatter to make sure that strings are properly escaped.
   * Ideally we'd replace this with something that handles prepared statements, but it
   * wouldn't work for
   *
   * @param string $value
   *   The value being formatted. 
   * @param string $key
   *   The name of the token being replaced. 
   * @param bool $raw
   *   TRUE implies the value should not be formatted for human consumption. 
   * @return string 
   *   Formatted value. 
   */
  public function format($value, $key, $raw = FALSE) {
    if ($raw) return $value;
    $db = $this->db;
    $value = $this->parmConvert($key, $value);
    if ($db) {
      if ($value==='' || $value ===NULL || $value === array()) {
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
      elseif (is_array($value)) {
        if ($value == array()) {
          $value = 'NULL';
        }
        else {
          // Build a array of values string
          $i=0;
          $val ='';
          foreach ($value as $v) {
            $i++;
            if ($i!=1) {
              $val .= ',';
            }
            $val .= $this->quote($v);
          }
          $value = $val;
        }
      }
      else  $value =  $this->quote($value);
    }
    return (string)$value;
  }

  public function searchTables($str) {
    $str .= '%';
    $sql = $this->searchTablesSQL();
    if ($sql) {
      $st = $this->db->prepare($sql);
      if ($st) $st->execute(array(':str' => $str));
      if ($st) {
        return $st->fetchAll(\PDO::FETCH_COLUMN, 0);
      }
      else {
        return NULL; 
      }
    }
    else {
      return NULL; 
    }
  }

  public function searchTableColumns($table, $str) {
    $str .= '%';
    $sql = $this->searchTableColumnsSQL();
    $info = $this->parseConnectionStr();
    $database = isset($info['dbname']) ? $info['dbname'] : @$info['database'];
    if ($sql) {
      $st = $this->db->prepare($sql);
      if ($st) $st->execute(array(':table' => $table, ':database' => $database, ':str' => $str));
      if ($st) {
        return $st->fetchAll(\PDO::FETCH_COLUMN, 0);
      }
      else {
        return NULL;
      }
    }
    else {
      return NULL; 
    }
  }
}