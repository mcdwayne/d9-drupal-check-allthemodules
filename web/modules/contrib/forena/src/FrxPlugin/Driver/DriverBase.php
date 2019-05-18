<?php
// $Id$
/**
 * @file
 * Class that defines default methods for access control in an DriverBase
 *
 */
namespace Drupal\forena\FrxPlugin\Driver;
use Behat\Mink\Exception\Exception;
use Drupal\forena\AppService;
use Drupal\forena\File\DataFileSystem;
use Drupal\forena\FrxAPI;
use \SimpleXMLElement;
use Symfony\Component\Yaml\Parser;

abstract class DriverBase implements DriverInterface {
  use FrxAPI;
  // Dependency injection for drupal code.
  public $name;
  public $conf;
  public $block_path;
  public $comment_prefix;
  public $comment_suffix;
  public $block_ext;
  public $block_extensions;
  public $types;
  public $block_name;
  public $fileSvc;
  protected $te;
  public $debug = FALSE;

  public function __construct($name, $conf, DataFileSystem $fileSystem) {
    $this->conf = $conf;
    $this->fileSvc = $fileSystem;
    $this->comment_prefix = '--';
    $this->block_ext = 'sql';
    $this->block_extensions = array('inc', 'sql', 'xml');
    $this->name = $name;
    $this->debug = @$conf['debug'];
  }

  /**
   * Implements the basic default security check of calling
   * an access method.
   *
   * @param string $arg
   * @return bool
   *   True indicates allowed access. 
   */
  public function access($arg) {
    $obj_access = TRUE;
    $f = @$this->conf['access callback'];
    if ($arg) {
      if ($f && is_callable($f)) {
        $obj_access =  $f($arg);
      }
      elseif (isset($this->conf['access block'])) {
        $block = @$this->conf['access block'];
        $path='';
        if (isset($this->conf['access path'])) $path = $this->conf['access path'];
        $obj_access =  $this->dataManager()->blockAccess($block, $path, $arg);
      }
    }
    return $obj_access;
  }

  protected function loadBlockFromFile($block_name) {
    $full_name = $this->name . '/' . $block_name;
    $php_class = $block_name;
    if ($this->fileSvc->exists($block_name . '.sql')) {
      $contents = $this->fileSvc->contents($block_name . '.sql');
      $block = $this->parseSQLFile($contents);
      $block['type'] = 'sql';
    }
    elseif ($this->fileSvc->exists($block_name . '.xml')) {
      $contents = $this->fileSvc->contents($block_name . '.xml');
      $block = $this->parseXMLFile($contents);
      $block['type'] = 'xml';
    }
    elseif ($this->fileSvc->exists($block_name . '.php')) {
      $php_file = $this->fileSvc->path($php_class . '.php');
      include_once $php_file;

      if (class_exists($php_class)) {
        $o = new $php_class();
        $block['type'] = 'php';
        $block['access'] = @$o->access;
        $block['object'] = $o;
        if (method_exists($o, 'tokens' )) {
          $block['tokens'] = $o->tokens();
        }
        elseif (isset($o->tokens)) {
          $block['tokens'] = $o->tokens;
        }
        else{
          $block['tokens'] = array();
        }
      }
    }
    else {
      return array();
    }
    $block['locked']=1;
    return $block;
  }


  /**
   * Load blcok data from filesystem
   * @param $block_name
   * @return array 
   *   Block definition. 
   */
  function loadBlock($block_name, $include=FALSE) {
    if ($include) $this->block_name = $block_name;
    $block = $this->loadBlockFromFile($block_name);
    return $block;
  }


  /**
   * Load tokens from block source
   */
  public function tokens($source) {
    $tokens = array();
    // If we have a regular expression token parser, then get the tokens out of the block.
    if ($this->te) {
      $tokens = @$this->te->tokens($source);
      $tokens = array_diff($tokens, array('current_user'));
      //check tokens in the where clause
    }

    return $tokens;
  }

  /**
   * Return data based on block definition.
   *
   * @param array $block
   *   Block definition.
   * @param bool|FALSE $raw_mode
   *   True to reutrn raw record/states or data structures
   * @return string
   */
  public function data(Array $block, $raw_mode=FALSE) {
    $xml = '';
    $right = @$block['access'];
    if ($block && $this->access($right)) {
      if ($raw_mode) $block['options']['return_type'] = 'raw';
      switch ($block['type'])  {
        case 'sql':
          $xml = $this->sqlData($block['source'], @$block['options']);
          break;
        case 'xml':
          $xml = $this->xmlData($block['source']);
          break;
        case 'php':
          $data = $this->dataManager()->dataSvc->currentContextArray();
          $xml = $this->phpData($block['object'], $data );
          break;
      }
    }
    return $xml;
  }

  /**
   * @param $search
   * @param $data_blocks
   * @TODO: Determine whether we still need this.
   */
  public function listDBBlocks($search, &$data_blocks) {
    $search = '%' . $search . '%';
    $sql = 'SELECT * from {forena_data_blocks} WHERE repository=:repos
      AND block_name like :search ';
    $rs = db_query($sql, array(':repos' => $this->name, ':search' => $search ));
    foreach ($rs as $block) {
      $data_blocks[] = $block->block_name;
    }
  }


  /**
   * Find all the blocks matching a provided search string
   *
   * @param string $search 
   *   partial block names to search for
   * @param array $block_list
   *   List of blocks to build 
   * @param string $subdir
   *   Subdirectory being examined.  Used primarily for recursion.
   * @TODO: MOve toDat Manageer
   */
  public function listDataBlocks($search, &$block_list, $subdir='') {
    $count=0;
    // First find files that match the search string
    $path = $this->fileSvc->includes[0] . '/';
    if ($subdir) $path .= $subdir . '/';
    $block_path = $path . '*' . $search . '*';
    // Find sql files
    // @TODO: Refactor to use file service to list files
    $d = glob($block_path);
    if ($d) foreach ($d as $file_name) {
      // Split off the extention
      $p = strripos($file_name, '.');
      if ($p!==FALSE) {
        $ext = substr($file_name, $p+1);
        $block_name = substr($file_name, 0, $p);
      }
      else {
        $ext = '';
        $block_name = $file_name;
      }
      switch ($ext) {
        case 'inc':
          require_once $file_name;
          $class = str_replace($path, '', $block_name);
          $methods = get_class_methods($class);
          if ($methods) foreach ($methods as $method) {
            if ($method != 'tokens') {
              $block_list[] = $class . '.' . $method;
            }
          }

          break;
        default:
          if (array_search($ext, $this->block_extensions)!==FALSE) {
            $block_list[] = str_replace($apth . '/', '', $block_name);

          }
      }
    }
    $count++;
    // Find directories
    $d = glob($path . '*');
    if ($d) foreach ($d as $dir_name) {
      if (is_dir($dir_name)) {
        $dir_name = str_replace($path . '/', '', $dir_name);
        $this->listDataBlocks($search, $block_list, $dir_name);
      }
    }
    // Date
    if (!$subdir && \Drupal::moduleHandler()->moduleExists('forena_query'))  {
      $this->listDBBlocks($search, $block_list);
    }
  }


  /**
   * Parse XML File contents into contents.
   * @param $contents
   * @return array
   */
  public function parseXMLFile($contents) {
    $comment = $this->comment_prefix;
    $trim = '->';
    $lines = explode("\n", $contents);
    $cnt = count($lines);
    $access = '';
    $i=0;
    $block = '';
    $data = '';
    while ($i<$cnt) {
      $l = trim($lines[$i], "\r");
      @list($d, $c) = explode($comment, $l, 2);
      if ($trim) $c = trim($c, $trim);
      if  ($c) {
        list($a, $o) = explode('=', $c, 2);
        $a = trim($a);
        if ($a && $o) {
          switch ($a) {
            case 'ACCESS':
              $access = trim($o);
              break;
            default:
          }

        }

      }
      if (strpos($l, $comment)!==0) {
        $data .= "$l\n";
      }
      $i++;
    }
    return array('access' => $access, 'source' => $data, 'tokens' => $this->tokens($data));
  }


  public function getSQLInclude($block_name) {
    //@TODO: allow relative block includes
    $block = $this->loadBlock($block_name, TRUE);
    if ($block && $block['type'] == 'sql') {
      return $block;
    }
    else {
      $this->app()->error("Include $block_name.sql not found");
      return NULL; 
    }
  }


  /**
   * Break the contents of a sql file down to its source.
   * @param $contents
   * @return array
   */
  public function parseSQLFile($contents) {
    $comment = $this->comment_prefix;
    $trim = $this->comment_suffix;
    $lines = explode("\n", $contents);
    $cnt = count($lines);
    $access = '';
    $i=0;
    $data = '';
    $file = '';
    $skip = FALSE;
    $in_info = FALSE;
    $found_case = FALSE;
    $info_text = '';
    $tokens = array();
    $options = array();
    $switch = '';
    while ($i<$cnt) {
      $l = trim($lines[$i], "\r");
      @list($d, $c) = explode($comment, $l, 2);
      if ($trim) $c = trim($c, $trim);
      if  ($c) {
        $c = trim($c);
        @list($a, $o) = explode('=', $c, 2);
        $a = trim($a);
        if (($a && $o) || $c == 'END' || $c == 'ELSE' || $c == 'INFO') {
          if ($c != 'INFO' ) {
            $in_info = false;
          }
          switch ($a) {
            case 'ACCESS':
              $access = trim($o);
              break;
            case 'SWITCH':
              $switch = trim($o);
              $found_case = FALSE;
              break;
            case 'CASE':
              $match = $this->te->replace($switch, TRUE) == $this->te->replace($o);
              if ($match) $found_case = TRUE;
              $skip = !$match;
              break;
            case 'IF':
              $skip = !$this->te->test(trim($o));
              break;
            case 'END':
              $skip = FALSE;
              $switch = '';
              break;
            case 'ELSE':
              $skip = $switch ? $found_case : !$skip;
              break;
            case 'INFO':
              $in_info = TRUE;
              break;
            case 'INCLUDE':
              if (!$skip) {
                $inc_block = $this->getSQLInclude(trim($o));
                if ($inc_block) {
                  $data .= $inc_block['source'];
                  $tokens = array_merge($tokens, $inc_block['tokens']);
                }
              }
              break;
          }

        }
        if ($a != 'ACCESS') $file .= "$l\n";

      }
      else {

        $file .= "$l\n";
      }
      if ($in_info) {
        if (strpos($l, $comment)!==0 && $l) $info_text .= "$l\n";
      } elseif (!$skip) {
        if (strpos($l, $comment)!==0 && $l) {
          $data .= "$l\n";
        }
      }

      $i++;
    }
    $tokens = array_merge($tokens, $this->tokens($contents));
    if ($info_text) {
      $parser = new Parser();
      try  {
        $options = $parser->parse($info_text);
      }
      catch (Exception $e) {
        $options = [];
      }
    }

    $block = array( 'source' => $data, 'file' => trim($file, " \n"),
      'tokens' => $tokens, 'options' => $options, 'access' => $access);
    return $block;
  }

  /**
   * Implement static XML functioin
   * @param string $xmlData 
   *   XML Source data from block load
   * @return SimpleXMLElement 
   *  XML node  
   */
  public function xmlData($xmlData) {
    $xml ='';
    if (trim($xmlData)) {
      try {
        $xml = new SimpleXMLElement($xmlData);
      } catch (\Exception $e) {
        $this->app()->error("Error processing xml\n", $e->getMessage() . "\n" . $xmlData);
      }
    }
    return $xml;
  }

  public function phpData($o, $parameters) {
    $data = NULL;

    if (is_object($o) && is_callable(array($o, 'data'))) {
      $data = $o->data($parameters);
    }
    return $data;
  }

  /**
   * Build the SQL clause based on builder data.
   * @param  $data
   * @return string 
   *   Where clause for SQL. 
   */
  public function buildFilterSQL($data) {
    $clause = '';
    $op = $data['op'];
    $i=0;
    if ($data['filter']) foreach ($data['filter'] as $cond) {
      $i++;
      $conj = ($i==1) ? '' : $op . ' ';
      if (isset($cond['filter'])) {
        $clause .= $conj . ' (' . $this->buildFilterSQL($cond) . " )\n";
      }
      else {

        switch ($cond['op']) {
          case 'IS NULL':
          case 'IS NOT NULL':
            $expr = $cond['field'] . ' ' . $cond['op'];
            break;
          default:
            $expr = $cond['field'] . ' ' . $cond['op'] . ' ' . $this->format($cond['value'], $cond['field'], array());
        }
        $clause .= ' ' . $conj . $expr;
      }
    }
    return $clause;
  }

  /**
   * Perform generic type conversion based on attributes.
   * @param  string $key 
   *   The token key for parameter replacement.
   * @param  string $value 
   *   The value of the parameter
   * @return mixed 
   *   Value of the parameter.
   */
  public function parmConvert($key, $value) {
    if (isset($this->types[$key]) && $this->types[$key]) {
      if ($value === NULL || $value ==='') {
        $value = NULL;
      }
      else {
        switch (strtolower($this->types[$key])) {
          case 'date':
            $time = @new \DateTime($value);
            if ($time) {
              $value   = date_format($time, 'Y-m-d H:i:s');
            }
            else {
              $value = NULL;
            }
            break;
          case 'unixtime':
            $time = @new \DateTime($value);
            if ($time) {
              $value   = $time->getTimestamp();
            }
            else {
              $value = NULL;
            }
            break;
          case 'numeric':
          case 'float':
            $value = (float)$value;
            break;
          case 'int':
          case 'integer':
            $value = (int)$value;
            break;
          case 'array':
            $value = (array)$value;
            break;
        }
      }
    }
    return $value;
  }

  /**
   * Method to return an array of tables that start with the string
   * indicated in $str
   * @param string $str 
   *   Partial table Name to search.
   * @return array
   *   Array of tables to search. :
   */
  public function searchTables($str) {
    return array();
  }

  public function searchTableColumns($table, $str) {
    return array();
  }

  public function searchTablesSQL() {

    switch ($this->db_type) {
      CASE 'mysql':
        $sql = "SHOW TABLES LIKE :str";
        break;
      CASE 'postgres':
      CASE 'postgresql':
      CASE 'pgsql':
        $sql = "SELECT tablename from (
            SELECT schemaname, tablename FROM pg_catalog.pg_tables
            UNION SELECT schemaname, viewname from pg_catalog.pg_views) v
            where schemaname NOT IN ('pg_catalog', 'information_schema') and tablename like :str
            order by 1";
        break;
      CASE 'oracle':
      CASE 'oci':
        $sql = "SELECT object_name FROM all_objects where object_type in ('TABLE','VIEW')
          AND owner not in ('SYS') AND object_name LIKE :str";
        break;
      CASE 'mssql':
        $sql = "SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'
             and table_name like :str";
        break;
      CASE 'sqlite':
        $sql = 'SELECT name FROM sqlite_master WHERE name like :str';
        break;
      default:
        $this->app()->error($this->app()->t('Unknown database type: %s', array('%s' => $this->db_type)),'error');
    }
    return $sql;
  }

  public function searchTableColumnsSQL() {

    switch ($this->db_type) {
      CASE 'mysql':
        $sql = "select column_name from information_schema.COLUMNS where
             table_schema = :database
             AND table_name = :table AND column_name like :str";
        break;
      CASE 'postgres':
      CASE 'postgresql':
      CASE 'pgsql':
        $sql = "SELECT column_name from
            information_schema.columns
            WHERE
              table_catalog = :database
              AND table_name = :table
              AND column_name like :str
            order by 1";
        break;
      CASE 'oracle':
      CASE 'oci':
        $sql = "SELECT column_name FROM all_tab_columns where
          table_name = :table_name
          AND column_name LIKE :str";
        break;
      CASE 'mssql':
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = :table and column_name like :str";
        break;
      CASE 'sqlite':
        $sql = 'PRAGMA table_info(:table)';
        break;
      default:
        $this->app()->error($this->app()->t('Unknown database type: %s', array('%s' => $this->db_type)),'error');
    }
    return $sql;
  }
}


