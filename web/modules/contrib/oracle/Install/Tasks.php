<?php

namespace Drupal\Driver\Database\oracle\Install;

use Drupal\Core\Database\Install\Tasks as InstallTasks;
use Drupal\Core\Database\Database;
use Drupal\Driver\Database\oracle\Connection;

/**
 * Specifies installation tasks for Oracle and equivalent databases.
 */
class Tasks extends InstallTasks {

  /**
   * The PDO driver name for Oracle and equivalent databases.
   *
   * @var string
   */
  protected $pdoDriver = 'oci';

  private $pdoBindLengthLimits = array(4000, 1332, 665);

  /**
   * {@inheritdoc}
   */
  public function name() {
    return t('Oracle');
  }

  /**
   * {@inheritdoc}
   */
  public function minimumVersion() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function connect() {
    try {
      // This doesn't actually test the connection.
      Database::setActiveConnection();

      // @TODO: make this dynamic.
      $dir = getcwd() . '/drivers/lib/Drupal/Driver/Database/oracle/resources';

      $this->determineSupportedBindSize();
      $this->createFailsafeObjects("{$dir}/table");
      $this->createFailsafeObjects("{$dir}/index");
      $this->createFailsafeObjects("{$dir}/sequence");
      $this->createObjects("{$dir}/function");
      $this->createObjects("{$dir}/procedure");
      $this->createSpObjects("{$dir}/type");
      $this->createSpObjects("{$dir}/package");
      $this->oracleQuery("begin dbms_utility.compile_schema(user); end;");

      $this->pass('Oracle has initialized itself.');
      Database::getConnection('default')->makePrimary();
    }
    catch (\Exception $e) {
      if ($e->getCode() == Connection::DATABASE_NOT_FOUND) {

        // Remove the database string from connection info.
        $connection_info = Database::getConnectionInfo();
        $database = $connection_info['default']['database'];
        unset($connection_info['default']['database']);
        $this->fail(t('Database %database not found. The server reports the following message when attempting to create the database: %error.', array('%database' => $database, '%error' => $e->getMessage())));
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Oracle helper for install tasks.
   */
  private function oracleQuery($sql, $args = NULL) {
    return Database::getConnection()->oracleQuery($sql, $args);
  }

  /**
   * Oracle helper for install tasks.
   */
  private function determineSupportedBindSize() {
    $this->failsafeDdl('create table bind_test (val varchar2(4000 char))');
    $ok = FALSE;

    foreach ($this->pdoBindLengthLimits as $length) {
      try {
        syslog(LOG_ERR, "trying to bind $length bytes...");
        $determined_size = $length;
        $this->oracleQuery('insert into bind_test values (?)', array(
          str_pad('a', $length, 'a'),
        ));
        syslog(LOG_ERR, "bind succeeded.");
        $ok = TRUE;
        break;
      }
      catch (\Exception $e) {
      }
    }

    if (!$ok) {
      throw new \Exception('unable to determine PDO maximum bind size');
    }

    $this->failsafeDdl("drop table oracle_bind_size");
    $this->failsafeDdl("create table oracle_bind_size as select $determined_size val from dual");
  }

  /**
   * Oracle helper for install tasks.
   */
  private function createSpObjects($dir_path) {
    $dir = opendir($dir_path);

    while ($name = readdir($dir)) {
      if (in_array($name, array('.', '..', '.DS_Store', 'CVS'))) {
        continue;
      }
      if (is_dir($dir_path . "/" . $name)) {
        $this->createSpObject($dir_path . "/" . $name);
      }
    }
  }

  /**
   * Oracle helper for install tasks.
   */
  private function createSpObject($dir_path) {
    $dir = opendir($dir_path);
    $spec = $body = "";

    while ($name = readdir($dir)) {
      if (substr($name, -4) == '.pls') {
        $spec = $name;
      }
      elseif (substr($name, -4) == '.plb') {
        $body = $name;
      }
    }

    $this->createObject($dir_path . "/" . $spec);
    if ($body) {
      $this->createObject($dir_path . "/" . $body);
    }
  }

  /**
   * Oracle helper for install tasks.
   */
  private function createObjects($dir_path) {
    $dir = opendir($dir_path);
    while ($name = readdir($dir)) {
      if (in_array($name, array('.', '..', '.DS_Store', 'CVS'))) {
        continue;
      }
      $this->createObject($dir_path . "/" . $name);
    }
  }

  /**
   * Oracle helper for install tasks.
   */
  private function createObject($file_path) {
    syslog(LOG_ERR, "creating object: $file_path");

    try {
      $this->oracleQuery($this->getPhpContents($file_path));
    }
    catch (\Exception $e) {
      syslog(LOG_ERR, "object $file_path created with errors");
    }
  }

  /**
   * Oracle helper for install tasks.
   */
  private function createFailsafeObjects($dir_path) {
    $dir = opendir($dir_path);

    while ($name = readdir($dir)) {
      if (in_array($name, array('.', '..', '.DS_Store', 'CVS'))) {
        continue;
      }
      syslog(LOG_ERR, "creating object: $dir_path/$name");
      $this->failsafeDdl($this->getPhpContents($dir_path . "/" . $name));
    }
  }

  /**
   * Oracle helper for install tasks.
   */
  private function failsafeDdl($ddl) {
    $this->oracleQuery("begin execute immediate '" . str_replace("'", "''", $ddl) . "'; exception when others then null; end;");
  }

  /**
   * Oracle helper for install tasks.
   */
  private function getPhpContents($filename) {
    if (is_file($filename)) {
      ob_start();
      require_once $filename;
      $contents = ob_get_contents();
      ob_end_clean();
      return $contents;
    }
    else {
      syslog(LOG_ERR, "error: file " . $filename . " does not exists");
    }
    return FALSE;
  }

}
