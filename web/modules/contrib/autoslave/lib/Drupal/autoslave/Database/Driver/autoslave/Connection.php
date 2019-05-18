<?php

/**
 * @file
 * Definition of Drupal\autoslave\Database\Driver\autoslave\Connection
 */

namespace Drupal\autoslave\Database\Driver\autoslave;

use Drupal\Core\Database\DatabaseExceptionWrapper;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseNotFoundException;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\Database\Connection as DatabaseConnection;

use PDO;

/**
 * @file
 * Database interface code for automatic slave selection depending on type of
 * query.
 *
 * @todo Ensure default method arguments will work if they differ from what's
 *       defined in class Database.
 */

include_once('defines.inc');

/**
 * @ingroup database
 * @{
 */

/**
 * Specific auto slave implementation of DatabaseConnection.
 */
class Connection extends DatabaseConnection {

  /**
   * List of tables that should always use "master" as target
   */
  protected $__master_tables = array();

  /**
   * List of tables that should use "master" as target in the current request
   */
  protected $__tables = array();
  public $__affected_tables = array();

  /**
   * Chosen master and slave
   */
  protected $__master;
  protected $__slave;
  protected $__system;
  public $max_expires = 0;

  /**
   * Force queries to master
   */
  private $__force_master = 0;

  /**
   * Setup booleans
   */
  private $__setup_session = FALSE;
  private $__setup_global = FALSE;

  /**
   * Automatic id assigment counter
   */
  static private $autoslave_id = 1;

  /**
   * Pool of connections
   */
  private $__pool = array();

  /**
   * Watchdog messages to log
   */
  private $__watchdog = array();

  /**
   * System is in read-only mode
   */
  protected $__readonly = FALSE;

  protected $__exception = FALSE;

  /**
   * Constructor.
   */
  public function __construct(array $connection_options = array()) {
    // Sanitize connection options
    $connection_options['master'] = !empty($connection_options['master']) ? $connection_options['master'] : array('master');
    if (!is_array($connection_options['master'])) {
      $connection_options['master'] = array($connection_options['master']);
    }

    $connection_options['slave'] = !empty($connection_options['slave']) ? $connection_options['slave'] : array('autoslave');
    if (!is_array($connection_options['slave'])) {
      $connection_options['slave'] = array($connection_options['slave']);
    }
    $connection_options['watchdog on shutdown'] = isset($connection_options['watchdog on shutdown']) ? $connection_options['watchdog on shutdown'] : AUTOSLAVE_WATCHDOG_ON_SHUTDOWN;
    $connection_options['replication lag'] = isset($connection_options['replication lag']) ? $connection_options['replication lag'] : AUTOSLAVE_ASSUMED_REPLICATION_LAG;
    $connection_options['global replication lag'] = isset($connection_options['global replication lag']) ?  $connection_options['global replication lag'] : AUTOSLAVE_GLOBAL_REPLICATION_LAG;
    $connection_options['invalidation path'] = isset($connection_options['invalidation path']) ?  $connection_options['invalidation path'] : NULL;

    $connection_options['init_commands'] = isset($connection_options['init_commands']) ?  $connection_options['init_commands'] : array();
    $connection_options['use system connection'] = isset($connection_options['use system connection']) ?  $connection_options['use system connection'] : FALSE;

    $this->__tables = !empty($connection_options['tables']) ? $connection_options['tables'] : array('sessions', 'sempahore');
    $this->__tables[] = 'autoslave_affected_tables';

    $this->connectionOptions = $connection_options;

    // Initialize and prepare the connection prefix.
    $this->setPrefix(isset($this->connectionOptions['prefix']) ? $this->connectionOptions['prefix'] : '');

    // Initialize force master tables
    if (!empty($this->__tables)) {
      $this->__master_tables = $this->__tables = array_combine($this->__tables, $this->__tables);
    }

    // Has master been forced before Database bootstrapping?
    if (!empty($_GLOBALS['autoslave_pre_bootstrap_force_master'])) {
      $this->forceMaster(1);
    }

    drupal_register_shutdown_function(array($this, 'logWatchdogMessages'));
  }

  /**
   * Set key is called immediatly after the constructor, so
   * now we can set up the connections.
   */
  function setKey($key) {
    if (!isset($this->key)) {
      $this->key = $key;
      $this->setupConnections();
      if (drupal_valid_test_ua()) {
        $this->connectionOptions['replication lag'] = 0;
        $this->connectionOptions['global replication lag'] = FALSE;
        return;
      }
    }
  }

  /**
   * Dispatch all methods defined in class PDO to the appropiate backend
   */
  public function beginTransaction() { return $this->getMasterConnection()->beginTransaction(); }
  public function errorCode() { return $this->getMasterConnection()->errorCode(); }
  public function errorInfo() { return $this->getMasterConnection()->errorInfo(); }
  public function exec($statement) { return $this->getMasterConnection()->exec($statement); }
  public function getAttribute($attribute) { return $this->getMasterConnection()->getAttribute($attribute); }
  static public function getAvailableDrivers() { $drivers = $this->getMasterConnection()->getAvailableDrivers(); $drivers[] = 'autoslave'; return $drivers; }
  public function lastInsertId($name = NULL) { return $this->getMasterConnection()->lastInsertId($name); }
  public function quote($string, $paramtype = NULL) { return $this->getMasterConnection()->quote($string, $paramtype); }
  public function setAttribute($attribute, $value) { return $this->getMasterConnection()->setAttribute($attribute, $value); }

  /**
   * Dispatch all methods defined in class Database to the appropiate backend
   */
  /**
   * "master" functions
   */
  public function inTransaction() { return $this->getMasterConnection()->inTransaction(); }
  public function transactionDepth() { return $this->getMasterConnection()->transactionDepth(); }
  public function rollback($savepoint_name = 'drupal_transaction') { return $this->getMasterConnection()->rollback($savepoint_name); }
  public function pushTransaction($name) { return $this->getMasterConnection()->pushTransaction($name); }
  public function popTransaction($name) { return $this->getMasterConnection()->popTransaction($name); }
  protected function popCommittableTransactions() { return $this->getMasterConnection()->popCommittableTransactions(); }
  protected function generateTemporaryTableName() { return $this->getMasterConnection()->generateTemporaryTableName(); }
  public function supportsTransactions() { return $this->getMasterConnection()->supportsTransactions(); }
  public function supportsTransactionalDDL() { return $this->getMasterConnection()->supportsTransactionalDDL(); }
  function commit() { return $this->getMasterConnection()->commit(); }

  /**
   * "slave" functions
   */
  protected function defaultOptions($full = TRUE) { 
    $options = $full ? $this->getSlaveConnection()->defaultOptions() : array(); 
    if ($this->__readonly) {
      $options['throw_exception'] = FALSE;
    }
    return $options;
  }
  public function getConnectionOptions() { return $this->getSlaveConnection()->getConnectionOptions(); }
  public function prefixTables($sql) { return $this->getSlaveConnection()->prefixTables($sql); }
  public function tablePrefix($table = 'default') { return $this->getSlaveConnection()->tablePrefix($table); }
  public function prepareQuery($query) { return $this->getSlaveConnection()->prepareQuery($query); }
  public function makeSequenceName($table, $field) { return $this->getSlaveConnection()->makeSequenceName($table, $field); }
  public function makeComment($comments) { return $this->getSlaveConnection()->makeComment($comments); }
  protected function filterComment($comment = '') { return $this->getSlaveConnection()->filterComment($comment); }
  protected function expandArguments(&$query, &$args) { return $this->getSlaveConnection()->expandArguments($query, $args); }

  /**
   * The following methods are absolutely necessary to overload manually (at least for MySQL)
   */
  public function version() { return $this->getSlaveConnection()->version(); }
  public function schema() { return $this->getMasterConnection()->schema(); }
  public function driver() { return 'autoslave'; }
  public function databaseType() { return $this->getSlaveConnection()->databaseType(); }
  public function query($query, array $args = array(), $options = array()) { 
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->deriveTargetFromQuery($query);
    }
    return $this->getSafeConnection($options['target'])->query($query, $args, $options);
  }
  public function queryRange($query, $from, $count, array $args = array(), array $options = array()) { 
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->deriveTargetFromQuery($query);
    }
    return $this->getSafeConnection($options['target'])->queryRange($query, $from, $count, $args, $options);
  }
  public function queryTemporary($query, array $args = array(), array $options = array()) {
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->determineMasterTarget();
    }
    $table = $this->getSafeConnection($options['target'])->queryTemporary($query, $args, $options);
    if ($table) {
      $this->addAffectedTable($table, FALSE);
    }
    return $table;
  }
  public function mapConditionOperator($operator) { return $this->getMasterConnection()->mapConditionOperator($operator); }
  public function nextId($existing_id = 0) { return $this->getMasterConnection()->nextId($existing_id); }

  public function select($table, $alias = NULL, array $options = array()) {
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->getTargetForTable($table);
    }
    $query = $this->getSafeConnection($options['target'])->select($table, $alias, $options);
    $query->addMetaData('autoslave_connection', array($this->getTarget(), $this->getKey()));
    drupal_bootstrap(DRUPAL_BOOTSTRAP_VARIABLES);
    include_once 'injector.inc';
    return $query->addTag('autoslave');
  }
  public function insert($table, array $options = array()) {
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->determineMasterTarget();
      $this->addAffectedTable($table);
    }
    return $this->getSafeConnection($options['target'])->insert($table, $options);
  }

  public function merge($table, array $options = array()) {
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->determineMasterTarget();
      $this->addAffectedTable($table);
    }
    return $this->getSafeConnection($options['target'])->merge($table, $options);
  }

  public function update($table, array $options = array()) {
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->determineMasterTarget();
      $this->addAffectedTable($table);
    }
    return $this->getSafeConnection($options['target'])->update($table, $options);
  }

  public function delete($table, array $options = array()) {
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->determineMasterTarget();
      $this->addAffectedTable($table);
    }
    return $this->getSafeConnection($options['target'])->delete($table, $options);
  }

  public function truncate($table, array $options = array()) {
    if (!$this->prepareAutoslaveTarget($options)) {
      $options['target'] = $this->determineMasterTarget();
      $this->addAffectedTable($table);
    }
    return $this->getSafeConnection($options['target'])->truncate($table, $options);
  }

  /**
   * Magic methods. Supports propeties/functions/methods not defined by the Database class
   */
  public static function __callStatic($method, $args) {
    return call_user_func_array(array($this->getMasterConnection()->get_class_name(), $method), $args);
  }

  public function __call($method, $args) {
    return call_user_func_array(array($this->getMasterConnection(), $method), $args);
  }

  public function __get($key) {
    return $this->getMasterConnection()->$key;
  }

  public function __set($key, $value) {
    $this->getMasterConnection()->$key = $value;
  }






  /**
   * Internal autoslave functions
   */

  /**
   * Get the current pool of available targets
   */
  function getPool() {
    return $this->__pool;
  }

  /**
   * Get the assumed maximum replication lag
   */
  function getReplicationLag() {
    return intval($this->connectionOptions['replication lag']);
  }

  /**
   * Get the assumed maximum replication lag
   */
  function getGlobalReplicationLag() {
    return $this->connectionOptions['global replication lag'];
  }

  /**
   * Check if a connection is available.
   *
   * @param $id
   *   Autoslave ID
   * @return mixed
   *   Exception object if error
   *   TRUE if available
   *   FALSE if available but flagged as down
   *   NULL if connection does not exist
   */
  function checkConnection($id) {
    $status = NULL;
    if (isset($this->__pool['all'][$id])) {
      $conninfo = &$this->__pool['all'][$id];
      if (!isset($conninfo['status']) || $conninfo['status'] === FALSE) {
        // Try it out ...
        $key = $conninfo['key'];
        $c = &DatabaseInternals::getConnections();
        $d = &DatabaseInternals::getDatabaseInfo();
        try {
          $d[$key]['autoslave_check'] = $conninfo;
          Database::getConnection('autoslave_check', $key);
          $databases = $this->loadInvalidationFile($key);
          $status = isset($databases[$key][$conninfo['target']][$conninfo['idx']]['status']) ? FALSE : TRUE;
        }
        catch (Exception $e) {
          $status = $e;
        }
        unset($d[$key]['autoslave_check']);
        unset($c[$key]['autoslave_check']);
      }
      else {
        $status = TRUE;
      }
    }
    return $status;
  }

  /**
   * Return the Autoslave ID for a given target.
   *
   * @param $target
   *   Name of target
   */
  function getAutoslaveId($target) {
    $d = DatabaseInternals::getDatabaseInfo();
    return isset($d[$this->getKey()][$target]['autoslave_id']) ? $d[$this->getKey()][$target]['autoslave_id'] : NULL;
  }

  function loadInvalidationFile($key) {
    $databases = array();
    if (isset($this->connectionOptions['invalidation path'])) {
      $file = $this->connectionOptions['invalidation path'] . "/autoslave-invalidation-$key.inc";
      if (file_exists($file)) {
        include $file;
      }
    }
    return $databases;
  }

  /**
   * Update invalidation file
   */
  function updateInvalidationFile($key, $target, $idx, $status) {
    $databases = $this->loadInvalidationFile($key);
    if ($databases) {
      $file = $this->connectionOptions['invalidation path'] . "/autoslave-invalidation-$key.inc";
      if (!isset($databases[$key][$target][$idx]['status'])) {
        if (!$status) {
          $databases[$key][$target][$idx]['status'] = FALSE;
        }
        else {
          return;
        }
      }
      else {
        if ($databases[$key][$target][$idx]['status'] === $status) {
          return;
        }
      }

      if ($status) {
        unset($databases[$key][$target][$idx]['status']);
      }
      else {
        $databases[$key][$target][$idx]['status'] = FALSE;
      }
      $output = '<' . '?php' . "\n";
      if (!is_numeric($idx)) {
        watchdog('autoslave', "[$key][$target][$idx] is not a valid connection!", array(), WATCHDOG_ERROR);
        return;
      }
      if (!empty($databases[$key])) {
        foreach ($databases[$key] as $target => $conninfos) {
          foreach ($conninfos as $idx => $conninfo) {
            if (isset($databases[$key][$target][$idx]['status'])) {
              $output .= '$databases["' . $key . '"]["' . $target . '"][' . $idx . ']["status"] = FALSE;' . "\n";
            }
          }
        }
      }
      $temp_name = tempnam(sys_get_temp_dir(), 'file');
      file_put_contents($temp_name, $output);
      rename($temp_name, $file);
    }
  }

  /**
   * Assign id's to connections, sanitize slave probabilities
   * and populate pools.
   */
  function setupConnections() {
    $key = $this->getKey();

    global $databases;

    $this->__pool = array(
      'master' => array(),
      'slave' => array(),
      'all' => array(),
    );

    $backends = array(
      'master' => $this->connectionOptions['master'], 
      'slave' => $this->connectionOptions['slave'],
    );

    foreach ($backends as $backend => $targets) {
      foreach ($targets as $target) {
        if (empty($databases[$key][$target])) {
          $conninfos = array();
        }
        elseif (empty($databases[$key][$target]['driver'])) {
          $conninfos = &$databases[$key][$target];
        }
        else {
          $databases[$key][$target] = array($databases[$key][$target]);
          $conninfos = &$databases[$key][$target];
        }

        foreach ($conninfos as $idx => &$conninfo) {
          if (empty($conninfo['autoslave_id'])) {
            $conninfo['target'] = $target;
            $conninfo['key'] = $key;
            $conninfo['idx'] = $idx;
            $conninfo['autoslave_id'] = self::$autoslave_id++;
            $conninfo['weight'] = isset($conninfo['weight']) ? intval($conninfo['weight']) : 100;

            // Parse the prefix information.
            if (!isset($conninfo['prefix'])) {
              // Default to an empty prefix.
              $conninfo['prefix'] = array(
                'default' => '',
              );
            }
            elseif (!is_array($conninfo['prefix'])) {
              // Transform the flat form into an array form.
              $conninfo['prefix'] = array(
                'default' => $conninfo['prefix'],
              );
            }

            $this->__pool['all'][$conninfo['autoslave_id']] = &$conninfo;
          }
          $this->__pool[$backend][$target][$conninfo['autoslave_id']] = &$this->__pool['all'][$conninfo['autoslave_id']];
        }
      }
    }
    $this->__pool['registered'] = $this->__pool['all'];

    if (isset($this->connectionOptions['invalidation path'])) {
      $target = $this->getTarget();
      $file = $this->connectionOptions['invalidation path'] . "/autoslave-invalidation-$key.inc";
      if (file_exists($file)) {
        include $file;
      }
    }

    // At this level, using Exceptions may result in endless loops ... so we die!
    if (empty($this->__pool['master'])) {
      die('There are no masters defined for AutoSlave. Please configure settings.php');
    }
    if (empty($this->__pool['slave'])) {
      die('There are no slaves defined for AutoSlave. Please configure settings.php');
    }

    $this->determineMaster();
    $this->determineSlave();

    return;
  }

  /**
   * Determine the slave to be used, and includes the master in the selection
   * if necessary.
   */
  function determineConnection($backend) {
    $targets = $this->__pool[$backend];
    foreach ($targets as $target => $conninfos) {
      // Gather weights
      $values = array();
      $weights = array();
      foreach ($conninfos as $conninfo) {
        // If we stumble upon an already connected connection, then use that one.
        if (isset($conninfo['connected'])) {
          if ($conninfo['connected'] === TRUE) {
            return $conninfo;
          }
          continue;
        }

        // Don't try an already failed one
        if (isset($conninfo['status']) && $conninfo['status'] !== TRUE) {
          continue;
        }

        $values[] = $conninfo;
        $weights[] = $conninfo['weight'];
      }

      // If no connection infos for this target, then try the next
      if (!$values) {
        continue;
      }

      // Weighted random selection!
      $conninfo = $this->rand_weighted($values, $weights);

      $d = &DatabaseInternals::getDatabaseInfo();
      $d[$conninfo['key']][$conninfo['target']] = $conninfo;
      return $conninfo;
    }

    if ($backend == 'master') {
      $this->goReadOnly();
    }

    $this->fatalThrow(new Exception("There are no connections available in the pool: $backend"));
  }

  /**
   * Throw an exception and disable watchdog on shutdown if necessary.
   */
  function fatalThrow($exception) {
    if (!$this->__exception) {
      // We remove any watchdog hooks, as they may cause a double-fault upon logging.
      if (!$this->connectionOptions['watchdog on shutdown']) {
        $implementations = &drupal_static('module_implements');
        $implementations['watchdog'] = array();
      }

      $this->__exception = $exception;
    }
    throw $exception;
  }

  /**
   * Put system into read only mode.
   */
  function goReadOnly() {
    $this->__readonly = TRUE;
    drupal_set_message(t('The system is currently in read-only mode. Any changes you make will not be saved!'), 'error');
  }

  /**
   * Determine the master to be use
   */
  function determineMaster($reload = FALSE) {
    if ($reload || !isset($this->__master)) {
      $this->__master = NULL;
      $conninfo = $this->determineConnection('master');
      $this->__master = $conninfo['autoslave_id'];
      if (!empty($conninfo['readonly'])) {
        $this->goReadOnly();
      }
      $this->determineSystemTarget($reload);
    }
    return $this->__master;
  }

  /**
   * Determine the master target
   */
  function determineMasterTarget() {
    if (!$this->__master) {
      $this->fatalThrow(new Exception("No master connection has been chosen"));
    }
    $conninfo = $this->__pool['all'][$this->__master];
    return $conninfo['target'];
  }

  /**
   * Determine the slave
   */
  function determineSlave($reload = FALSE) {
    if ($reload || !isset($this->__slave)) {
      $this->__slave = NULL;
      $conninfo = $this->determineConnection('slave');
      $this->__slave = $conninfo['autoslave_id'];
    }
    return $this->__slave;
  }

  /**
   * Determine the slave to be used, and includes the master in the selection
   * if necessary.
   */
  function determineSlaveTarget() {
    if (!$this->__slave) {
      $this->fatalThrow(new Exception("No slave connection has been chosen"));
    }
    $conninfo = $this->__pool['all'][$this->__slave];
    return $conninfo['target'];
  }

  /**
   * Determine the target to be used for system maintenance (affected tables).
   */
  function determineSystemTarget($reload = FALSE) {
    if (!$this->connectionOptions['use system connection']) {
      return $this->determineMasterTarget();
    }
    elseif ($reload || !isset($this->__system)) {
      $conninfo = $this->__pool['all'][$this->determineMaster()];
      $d = &DatabaseInternals::getDatabaseInfo();
      $this->__system = self::$autoslave_id++;
      $target = $this->getTarget() . '_autoslave_system';
      $conninfo['autoslave_id'] = $this->__system;
      $conninfo['target'] = $target;
      $conninfo['idx'] = 0;
      $conninfo['init_commands'] = $this->connectionOptions['init_commands'];
      $d[$conninfo['key']][$target] = $conninfo;
      $this->__pool['all'][$this->__system] = $conninfo;
    }
    return $this->__pool['all'][$this->__system]['target'];
  }

  /**
   * Store watchdog message for later ... watchdogging!?
   */
  function watchdog($name, $msg, $args, $level) {
    $this->__watchdog[] = array($name, $msg, $args, $level);
  }

  /**
   * Log all registered messages to watchdog
   */
  function logWatchdogMessages() {
    foreach ($this->__watchdog as $log) {
      call_user_func_array('watchdog', $log);
    }
  }


  /**
   * Invalidate a connection, so that a failover connection may be attempted.
   */
  function invalidateConnection($id) {
    $conninfo = $this->__pool['all'][$id];
    $key = $conninfo['key'];
    $target = $conninfo['target'];
    $idx = $conninfo['idx'];

    $database_info = &DatabaseInternals::getDatabaseInfo();
    $conninfo = $database_info[$key][$target];
    global $databases;

    unset($databases[$key][$target][$idx]);
    if (empty($databases[$key][$target])) {
      // No more slaves, remove completely
      unset($database_info[$key][$target]);
      unset($databases[$key][$target]);
    }
    else {
      // Reindex target array for random select purposes
      $targets = array_values($databases[$key][$target]);
      $database_info[$key][$target] = $targets[mt_rand(0, count($targets) - 1)];
    }

    $this->updateInvalidationFile($key, $target, $idx, FALSE);

    $this->watchdog('autoslave', "Invalidated connection [@key][@target]@idx", array(
      '@key' => $key,
      '@target' => $target,
      '@idx' => isset($idx) ? "[$idx]" : '',
    ), WATCHDOG_ALERT);

    if ($id == $this->__slave) {
      $this->determineSlave(TRUE);
      $target = $this->determineSlaveTarget();
    }
    if ($id == $this->__master) {
      $this->determineMaster(TRUE);
      $target = $this->determineMasterTarget();
    }
    return $target;
  }

  /**
   * Get a connection for the given target.
   * Invalidates and redetermines if necessary.
   */
  function getSafeConnection($target) {
    $key = $this->getKey();
    $id = $this->getAutoslaveId($target);
    try {
      $ignoreTargets = &DatabaseInternals::getIgnoreTargets();
      unset($ignoreTargets[$key][$target]);
      $result = Database::getConnection($target, $key);
      if ($id) {
        $this->__pool['all'][$id]['connected'] = TRUE;
        $this->__pool['all'][$id]['status'] = TRUE;
      }
      return $result;
    }
    catch (Exception $e) {
      if ($id) {
        $this->__pool['all'][$id]['connected'] = FALSE;
        $this->__pool['all'][$id]['status'] = $e;
        $target = $this->invalidateConnection($id);
        try {
          return $this->getSafeConnection($target, $key);
        }
        catch (Exception $e) {
          throw $e;
        }
      }
      throw $e;
    }
  }

  /**
   * Get the master connection
   *
   * @return DatabaseConnection
   */
  function getMasterConnection() {
    return $this->getSafeConnection($this->determineMasterTarget());
  }

  /**
   * Get the slave connection
   *
   * @return DatabaseConnection
   */
  function getSlaveConnection() {
    if ($this->forceMaster()) {
      return $this->getMasterConnection();
    }
    else {
      return $this->getSafeConnection($this->determineSlaveTarget());
    }
  }

  /**
   * Check if a database target is available
   *
   * @param $target
   *   Database target
   * @param $key
   *   Database key
   *
   * @return boolean
   *   TRUE if target is available.
   */
  public function isTargetAvailable($target) {
    $d = DatabaseInternals::getDatabaseInfo();
    $result = isset($d[$this->getKey()][$target]);
    return $result;
  }

  /**
   * Prepare options array for autoslave determination.
   *
   * @param &$options
   *   Connection options for a query.
   */
  function prepareAutoslaveTarget(&$options) {
    $options += $this->defaultOptions(FALSE);
    if (empty($options['target'])) { 
      $options['target'] = $this->getTarget();
      return FALSE;
    }
    if ($options['target'] == 'slave') {
      // Direct to the autoslave if 'slave' is explicitely chosen.
      $options['target'] = $this->determineSlaveTarget();
    }
    if (!$this->isTargetAvailable($options['target'])) {
      $options['target'] = $this->getTarget();
      return FALSE;
    }
    if ($options['target'] == $this->getTarget()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get the target for a given query
   */
  function deriveTargetFromQuery($query) {
    switch ($this->deriveBackendFromQuery($query)) {
      case 'master':
        return $this->determineMasterTarget();
      case 'slave':
        return $this->determineSlaveTarget();
      default:
        $this->fatalThrow(new Exception("Unexpected error! No target found for query! This exception should never be thrown!"));
    }
  }

  /**
   * Determine the backend to use based on a query.
   *
   * @param $query
   *   The query to examine.
   */
  private function deriveBackendFromQuery($query) {
    $this->ensureAffectedTables();

    $is_write_query = preg_match('/^\s*('.
      'UPDATE|INSERT|REPLACE|DELETE|'.
      'ALTER|CREATE|DROP|TRUNCATE|RENAME|'.
      'BEGIN|START\s+TRANSACTION|COMMIT|ROLLBACK|'.
      'RELEASE|SAVEPOINT|'.
      '(.*FOR UPDATE$)|(.*LOCK IN SHARE MODE$)'.
    ')\b/i', $query);

    // Find all tables used in the query.
    preg_match_all('@\{(\w+)\}@', $query, $matches);
    $tables = $matches[1];

    if ($is_write_query) {
      // Even if forceMaster() is true, we still need to tag tables that have been written to,
      // in case we go back to forceMaster() false later on.
      $this->addAffectedTables($tables);
      return 'master';
    }
    elseif ($this->forceMaster()) {
      return 'master';
    }
    else {
      return array_intersect($tables, $this->__tables) ? 'master' : 'slave';
    }
  }

  /**
   * Get the target for a table
   *
   * @param $table
   *   Table to get target for
   * @return string
   *   Target
   */
  private function getTargetForTable($table) {
    $this->ensureAffectedTables();
    return $this->forceMaster() || isset($this->__tables[$table]) ? $this->determineMasterTarget() : $this->determineSlaveTarget();      
  }

  /**
   * Get/set force master counter
   *
   * @param $force_master
   *   Number to increase force master counter with
   */
  public function forceMaster($force_master = NULL) {
    if (isset($force_master)) {
      $this->__force_master += $force_master;
    }
    return $this->__force_master;
  }

  /**
   * Redeclare the master target as the default target.
   */
  public function hardSwitch() {
    // @todo Revisit this again, since it probably doesn't work anymore ...
    $connection_info = $this->__pool['all'];
    Database::renameConnection('default', 'autoslave_original_default');
    Database::addConnectionInfo('default', 'default', $connection_info[$this->__master]);
  }

  /**
   * Get list of affected tables.
   */
  public function getAffectedTables() {
    return $this->__tables;
  }

  /**
   * Add tables to list of affected tables.
   *
   * @param $tables
   *   Array of tables affected by write
   * @param $update_session
   *   Update session with new expiration for replication lag mitigation
   */
  public function addAffectedTables($tables, $update_session = TRUE) {
    // Only session-track tables that are not already on "always-master"
    $tables = array_diff($tables, $this->__master_tables);
    $this->__tables = array_unique(array_merge($this->__tables, $tables));

    $time = time();
    $lag = $this->getReplicationLag();
    if ($lag > 0 && $update_session) {
      $key = $this->getKey();
      $target = $this->getTarget();
      foreach ($tables as $table) {
        // Reflag tables with timestamp later, if we're inside a transaction.
        $expires = $time + $lag;
        $this->max_expires = $this->max_expires < $expires ? $expires : $this->max_expires;

        if ($this->getGlobalReplicationLag()) {
          $this->__affected_tables[$key][$target][$table] = $expires;
          try {
            $conn = Database::getConnection($this->determineSystemTarget(), 'default');
            $rows = $conn->update('autoslave_affected_tables')
              ->fields(array(
                'expires' => $expires,
                'db_uniq' => uniqid('autoslave', TRUE)
              ))
              ->condition('db_key', $key)
              ->condition('db_target', $target)
              ->condition('affected_table', $table)
              ->execute();
            if (!$rows) {
              $conn->insert('autoslave_affected_tables')
                ->fields(array(
                  'db_key' => $key,
                  'db_target' => $target,
                  'affected_table' => $table,
                  'expires' => $expires
                ))
                ->execute();
            }
          }
          catch (Exception $e) {
            // Just ignore error for now
          }
        }
        else {
          $_SESSION['autoslave_affected_tables'][$key][$target][$table] = $expires;
          $_SESSION['autoslave_affected_tables_version'] = AUTOSLAVE_VERSION;
        }
      }
      if ($this->max_expires) {
        $_SESSION['ignore_slave_server'] = $this->max_expires;
      }
    }
    return $this->__tables;
  }

  /**
   * Add one affected table
   *
   * @param $table
   *   Table affected by write
   * @param $update_session
   *   Update session with new expiration for replication lag mitigation
   */
  public function addAffectedTable($table, $update_session = TRUE) {
    return $this->addAffectedTables(array($table => $table), $update_session);
  }

  /**
   * Ensure that tables affected by write from previous requests are flagged,
   * so that queries for these tables will go to the master.
   */
  function ensureAffectedTables() {
    $key = $this->getKey();

    // Load globally affected tables
    if (!$this->__setup_global) {
      $this->__setup_global = TRUE;

      if ($this->getGlobalReplicationLag()) {
        $tables = array();
        try {
          $conn = Database::getConnection($this->determineSystemTarget(), 'default');
          $tables = $conn->select('autoslave_affected_tables', 'a')
                      ->fields('a', array('db_key', 'db_target', 'affected_table'))
                      ->condition('a.expires', $_SERVER['REQUEST_TIME'], '>')
                      ->execute()
                      ->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
          // Just ignore error for now
        }
        foreach ($tables as $table) {
          $connection = Database::getConnection($table['db_target'], $table['db_key']);
          $connection->addAffectedTable($table['affected_table'], FALSE);
        }

        // No need to use session when global replication lag is enabled.
      }
    }

    // Use connection to master for affected tables within the given assumed lag time interval.
    if (!$this->__setup_session &&
      function_exists('drupal_session_started') &&
      drupal_session_started()
    ) {
      $this->__setup_session = TRUE;

      if ($this->getGlobalReplicationLag()) {
        $_SESSION['autoslave_affected_tables'] = NULL;
        $_SESSION['autoslave_affected_tables_version'] = NULL;
        unset($_SESSION['autoslave_affected_tables']);
        unset($_SESSION['autoslave_affected_tables_version']);
      }
      elseif (!empty($_SESSION['autoslave_affected_tables'])) {
        // Ensure BC for running sessions.
        $version = isset($_SESSION['autoslave_affected_tables_version']) ? $_SESSION['autoslave_affected_tables_version'] : '1.3';
        if (version_compare($version, '1.3', '<=')) {
          $_SESSION['autoslave_affected_tables'] = array(
            'default' => $_SESSION['autoslave_affected_tables']
          );
          $_SESSION['autoslave_affected_tables_version'] = AUTOSLAVE_VERSION;
        }

        if (isset($_SESSION['autoslave_affected_tables'][$key])) {
          // We use server request time instead of time() for the sake of db's with isolation level snapshots.
          foreach ($_SESSION['autoslave_affected_tables'][$key] as $target => $tables) {
            $connection = Database::getConnection($target, $key);
            if ($connection->driver() != 'autoslave') {
              continue;
            }

            foreach ($tables as $table => $expires) {
              if ($_SERVER['REQUEST_TIME'] <= $expires) {
                $connection->addAffectedTable($table, FALSE);
              }
              else {
                unset($_SESSION['autoslave_affected_tables'][$key][$target][$table]);
              }
            }
            // If no affected tables, remove the variable from session.
            if (empty($_SESSION['autoslave_affected_tables'][$key][$target])) {
              unset($_SESSION['autoslave_affected_tables'][$key][$target]);
            }
          }
          // If no affected tables, remove the variable from session.
          if (empty($_SESSION['autoslave_affected_tables'][$key])) {
            unset($_SESSION['autoslave_affected_tables'][$key]);
          }
        }

        // If no affected tables, remove the variable from session.
        if (empty($_SESSION['autoslave_affected_tables'])) {
          unset($_SESSION['autoslave_affected_tables']);
          unset($_SESSION['autoslave_affected_tables_version']);
        }
      }
    }
  }

  /**
   * weighted_random_simple() from http://w-shadow.com/blog/2008/12/10/fast-weighted-random-choice-in-php/
   * modified by Thomas Gielfeldt to presort by weights.
   * Pick a random item based on weights.
   *
   * @param array $values Array of elements to choose from 
   * @param array $weights An array of weights. Weight must be a positive number.
   * @return mixed Selected element.
   */
  function rand_weighted($values, $weights){
    asort($weights);
    $num = mt_rand(1, array_sum($weights));
    $n = 0;
    foreach ($weights as $i => $weight) {
      $n += $weights[$i];
      if($n >= $num){
        return $values[$i];
      }
    }
    return NULL;
  }

  public function createDatabase($database) {}

}

/**
 * Get access to Database internal properties
 */
class DatabaseInternals extends Database {
  static function &getDatabaseInfo() {
    return self::$databaseInfo;
  }

  static function &getConnections() {
    return self::$connections;
  }

  static function &getIgnoreTargets() {
    return self::$ignoreTargets;
  }
}

