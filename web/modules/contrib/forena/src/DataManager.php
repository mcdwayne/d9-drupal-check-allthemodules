<?php
/**
 * @file DataManager.inc
 * Enter description here ...
 * @author davidmetzler
 *
 */

namespace Drupal\forena;
use Drupal\forena\Context\DataContext;
use Drupal\forena\File\DataFileSystem;

class DataManager {
  // Singleton factory.
  protected static $instance;

  // The data context service in use by the app.
  public $dataSvc;
  public $app;
  public $repositories;
  public $drivers = [
    'FrxDrupal' => '\Drupal\forena\FrxPlugin\Driver\FrxDrupal',
    'FrxFiles' => '\Drupal\forena\FrxPlugin\Driver\FrxFiles',
    'FrxMSSQL' => '\Drupal\forena\FrxPlugin\Driver\FrxMSSQl',
    'FrxOracle' => '\Drupal\forena\FrxPlugin\Driver\FrxOracle',
    'FrxPDO' => '\Drupal\forena\FrxPlugin\Driver\FrxPDO',
    'FrxPostgres' => '\Drupal\forena\FrxPlugin\Driver\FrxPostgres',
  ];

  /**
   * Returns the Data Manager instance.
   * @return \Drupal\forena\DataManager
   * Static factory metion
   */
  public static function instance($force_new = FALSE) {
    if ((NULL === static::$instance) || $force_new) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  //Determine data sources.
  public function __construct() {
    global $_forena_repositories;
    // Initialize services
    $app = AppService::instance();
    $this->dataSvc = new DataContext();
    $this->dataSvc->setContext('site', $app->siteContext);

    // Empty repository so we need to initialize
    // Build the default sample one
    $providers = array();

    // Load the repository list from the global settings.php file.
    if ($_forena_repositories) {
      $providers = $_forena_repositories;
    }

    $this->drivers = $app->getDriverPlugins();
    $data = AppService::instance()->getForenaProviders();
    foreach ($data as $module => $provider) {
      if (isset($provider['data'])) {
        $providers = array_merge($providers, $provider['data']);
      }
    }


    // Retrieve the repositories defined in the database;
    $results = db_query('SELECT * FROM {forena_repositories}');
    foreach ($results as $r) {
      if ($r->config) {
        $new_r = unserialize($r->config);
      }
      else {
        $new_r = array();
      }
      $r_name = $r->repository;
      if (is_array(@$providers[$r_name])) {
        $new_r = array_merge($new_r, $providers[$r_name]);
      }
      else {
        $new_r['source'] = 'user';
      }
      $new_r ['title'] = $r->title;
      $new_r ['enabled'] = $r->enabled;

      $providers[$r_name] = $new_r;
    }

    if ($_forena_repositories) {
      array_merge($providers, $_forena_repositories);
    }

    \Drupal::moduleHandler()->alter('forena_data_provider', $providers);

    $this->repositories = $providers;

  }

  /**
   * Load repository
   * @param string $name
   *   Name of the repository
   * @return \Drupal\forena\FrxPlugin\Driver\DriverBase
   */
  public function repository($name) {
    // Now determine if the object exists
    $object = NULL;
    if (isset($this->repositories[$name])) {
      if (@!is_object($this->repositories[$name]['data'])) {
        $this->loadRepositoryConfig($this->repositories[$name], $name);
      }
      $object = $this->repositories[$name]['data'];
    }
    else {
      AppService::instance()->error('Undefined repository' . $name, "Undefined Repository: $name ");
    }
    return $object;
  }

  // Putting this in a function to sandbox the repository settings
  protected function loadRepositoryConfig(&$repo, $name) {
    // First determine if the class file exisits
    $path = @$repo['source'];
    $conf = array();

    if (file_exists($path . '/settings.php')) {
      // Override with repository specific data
      include($path . '/settings.php');
    }

    $repo = array_merge($conf, $repo);
    if (!isset($repos['data'])||!is_object($repo['data'])) $repo['data'] = $this->createDataSource($repo, $path, $name);

  }

  /**
   * Load the driver class based on the class name.
   *
   * @param string $name
   * @return \Drupal\forena\FrxPlugin\Driver\DriverBase The data provider object
   */
  public function createDataSource($conf, $repo_path, $repos_name) {
    $o = NULL;
    @$name = $conf['driver'];
    $drivers = $this->drivers;
    // Instantiate the Data Driver object.
    if (isset($drivers[$name]) && class_exists($drivers[$name])) {
      $fileSystem = new DataFileSystem($name, $repo_path, $this);
      $class = $drivers[$name];
      $o = new $class($repos_name, $conf, $fileSystem);
    }
    else {
      AppService::instance()->error('Driver not found for ' . $conf['title']);
    }
    return $o;
  }


  /**
   * Load Block
   * Enter description here ...
   * @param $data_block string name of data block.
   * @return array 
   *   block definition. 
   */
  public function loadBlock($data_block) {
    $block = array();
    list($provider, $block_name) = explode('/', $data_block, 2);
    $repos = @$this->repositories[$provider];
    if (isset($repos['enabled']) && !$repos['enabled']) {
      return array();
    }

    $o = $this->repository($provider);
    if ($o) {
      $block = $o->loadBlock($block_name);
    }
    return $block;
  }

  /**
   * Save a data block ...
   * @param string $data_block
   * @param array $data
   */
  public function saveBlock($data_block, $data) {
    list($provider, $block_name) = explode('/', $data_block, 2);
    $file = isset($data['access']) ?  "--ACCESS=" . $data['access'] . "\n" . $data['file'] : $data['file'];
    $this->repository($provider)->fileSvc->save($data_block .'.sql', $file);
  }

  /**
   * Save a data block ...
   * @param string $data_block
   */
  public function deleteBlock($data_block) {
    list($provider) = explode('/', $data_block, 2);
    $this->repository($provider)->fileSvc->delete($data_block . '.sql');
  }



  /**
   * Extract the data by running a block
   *
   * @param $data_block String name ob block to load
   * @return \SimpleXMLElement
   */
  function data($data_block, $raw_mode=FALSE, array $data = []) {
    list($provider) = explode('/', $data_block, 2);
    //Intstantiate the provider
    $o = $this->repository($provider);
    $repos = @$this->repositories[$provider];
    if (isset($repos['enabled']) && !$repos['enabled']) {
      return '';
    }
    //Populate user callback.
    if (isset($repos['user callback'])) {
      $user_fn = $repos['user callback'];
      if (is_callable($user_fn)) {
        $current_user =   $user_fn();
        $this->dataSvc->setValue('current_user', $current_user);
      }
    }
    if ($data) foreach ($data as $key=>$value) {
      $this->dataSvc->setValue($key, $value);
    }


    $xml = NULL;
    if ($o) {
      $block = $this->loadBlock($data_block);
      $xml = $o->data($block, $raw_mode);
    }
    return $xml;
  }

  /**
   * Execute sql on a provider
   * @param $provider String Data provider index to reference
   * @param $sql String sql command to execute
   * @return \SimpleXMLElement | array 
   *   Data returned by executed sql query. 
   */
  public function sqlData($provider, $sql, $parms = array()) {
    $xml = '';

    //Intstantiate the provider
    /** @var \Drupal\forena\FrxPlugin\Driver\DriverBase $o */
    $o = $this->repository($provider);

    $repos = @$this->repositories[$provider];
    if (isset($repos['enabled']) && !$repos['enabled']) {
      return '';
    }
    //Populate user callback.
    if (isset($repos['user callback'])) {
      $user_fn = $repos['user callback'];
      if (is_callable($user_fn)) {
        $current_user =   $user_fn();
        $parms['current_user'] = $current_user;
      }
    }


    if ($o && $sql) {
      $this->dataSvc->push($parms, 'parm');

      // Parse the sql file
      $data = $o->parseSQLFile($sql);
      //now get the built SQL back
      $sql = $data['source'];
      $xml = $o->sqlData($sql, @$data['options']);
      $this->dataSvc->pop();
    }
    return $xml;
  }

  /**
   * Parse a block into its data
   * @param string $source
   *   Text data of the SQL block definition
   * @return array
   *   block definition.
   */
  public function sqlBlock($provider, $source) {      //Instantiate the provider
    $o = $this->repository($provider);

    $repos = @$this->repositories[$provider];
    if (isset($repos['enabled']) && !$repos['enabled']) {
      return '';
    }
    if ($o) {
      return $o->parseSQLFile($source);
    }
    else {
      return NULL; 
    }
  }

  /**
   * Build an SQL statement from the data provider
   * @param string $provider
   *   Data provider name
   * @param array $builder
   *   Build information.
   * @return string
   *   SQL query. 
   */
  public function buildSQL($provider, $builder) {
    $repos = @$this->repositories[$provider];
    if (isset($repos['enabled']) && !$repos['enabled']) {
      return '';
    }
    $o = $this->repository($provider);
    $sql = "SELECT * FROM (\n";
    $sql .= '--INCLUDE=' . $builder['block_name'] . "\n";
    $sql .= ") t\n";
    if (!empty($builder['where'])) {
      $sql .= "WHERE " . $o->buildFilterSQL($builder['where']);
    }
    return $sql;

  }


  /**
   * Check access control using the block in a data block.  In this case
   * public assess returns true.
   * @param string $block
   *   Repository block used to test access
   * @param string $path
   *   xpath to user right within xml data.
   * @param string $access
   *   Access to test
   * @pararm $cache
   *   Allow caching of block access check
   * @return boolean
   */
  function blockAccess($block, $path, $access, $cache=TRUE) {
    // PUBLIC always returns true.
    if ($access=='PUBLIC') return TRUE;
    if (!isset($_SESSION['forena_access'])) $_SESSION['forena_access'] = array();
    if ($cache && isset($_SESSION['forena_access'][$block])) {
      $rights = $_SESSION['forena_access'][$block];
    }
    else {
      $rights = array();
      // Get the block from the repository

      $this->dataSvc->push(array(), 'frx-block-access');
      $data = $this->data($block);
      $this->dataSvc->pop();
      if ($data) {
        if (!$path) {
          $path ='*/*';
        }
        $nodes = $data->xpath($path);
        if ($nodes) foreach ($nodes as $node) {
          $rights[] = (string)$node;
        }
        $_SESSION['forena_access'][$block]=$rights;
      }
    }
    foreach ($rights as $right) {
      if ($access == $right) return TRUE;
    }
    return FALSE;
  }


  /*
   * Recieves a datablock and returns an array of values from the data block.
   * @data_block: name of the data block to be invoked for values
   * @field: Specific field name within the data block. The values returned will only
   * come from this field.
   * @params: filtering for the data block
   * @where_clause: Where clause for data block to be filtered against.
   *
   */
  function dataBlockParams($data_block, $field, $label) {
    /** @var \SimpleXMLElement $xml */
    $xml = $this->data($data_block);

    $list = array();
    if ($xml) {
      $path = ($field) ? $field : '*[1]';
      $label_path = ($label) ? $label : '*[2]';


      //walk through the $xml.
      //$rows = $xml->xpath('*');

      if ($xml) {
        /** @var \SimpleXMLElement $row */
        foreach ($xml as $row) {
          $value = $row->xpath($path);
          $label_field = $row->xpath($label_path);
          $label_value =  $label_field ? (string)$label_field[0] : (string)$value[0];
          $list[(string)$value[0]] = (string)$label_value;
        }
      }
    }
    return $list;
  }

  public function listRepos() {
    $r = array();

    foreach ($this->repositories as $k=>$repos) {
      if (forena_user_access_check("access $k data")) {
        $r[$k] = $repos['title'];
      }
    }
    asort($r);
    return $r;
  }

}