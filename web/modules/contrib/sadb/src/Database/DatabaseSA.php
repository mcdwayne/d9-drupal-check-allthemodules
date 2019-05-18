<?php
/**
 * Created by PhpStorm.
 * User: nwanasinghe
 * Date: 05/09/2016
 * Time: 17:16
 */

namespace Drupal\sadb\Database;


//use Drupal\Core\Database\Database;

use Exception;
use Drupal\Core\Database\DriverNotSpecifiedException;

class DatabaseSA {

  protected $databaseInfo = array(
    'driver' => 'sqlite',
    'database' => '.ht.sqlite',
  );

  private static $config = [
    'mandatory'=>[
      'sqlite'=>[
        'database',
        'driver',
      ],
      'mysql'=>[
        'database',
        'driver',
        'username',
      ],
    ]
  ];

  /**
   * Create new SA Database object.
   * @param array $connection_options
   * @throws Exception
   */
  function __construct(array $connection_options){
    if (!$driver = $connection_options['driver']) {
      throw new DriverNotSpecifiedException("Driver not specified for this database connection");
    }

    //Check mandatory fields.
    $fields = self::$config['mandatory'][$driver];
    foreach($fields as $key => $field){
      if(empty($connection_options[$field])){
        throw new Exception("Mandatory field not specified : $field");
      }
    }

    //Set settings.
    foreach($connection_options as $key => $value){
      $this->databaseInfo[$key] = $value;
    }
  }

  /**
   * Get Drupal Database Connection.
   * @return \Drupal\Core\Database\Connection
   */
  public function getConnection() {

    //Get driver class.
    if (!empty($this->databaseInfo['namespace'])) {
      $driver_class = $this->databaseInfo['namespace'] . '\\Connection';
    }
    else {
      // Fallback for Drupal 7 like settings.
      $driver = $this->databaseInfo['driver'];
      $driver_class = "Drupal\\Core\\Database\\Driver\\{$driver}\\Connection";
    }

    $pdo_connection = $driver_class::open($this->databaseInfo);
    $new_connection = new $driver_class($pdo_connection, $this->databaseInfo);


//    // If we have any active logging objects for this connection key, we need
//    // to associate them with the connection we just opened.
//    if (!empty(self::$logs[$key])) {
//      $new_connection->setLogger(self::$logs[$key]);
//    }

    return $new_connection;
  }
}