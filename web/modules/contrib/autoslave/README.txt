== Installation ==

Copy the autoslave directory from the modules directory to core/lib/Drupal/Core/Database/Driver/, e.g.

%> cd [my-drupal-installation]
%> cp -r sites/all/modules/autoslave/autoslave core/lib/Drupal/Core/Database/Driver/


== Configuration ==
To reduce writes to the database it is recommended to use Memcache (or similar) for session and cache, and syslog (or similar) for logging instead of dblogging.
The following examples are based on a non-memcache setup (the lock_inc is the only memcache specific configuration).

=== Simple ===
1 master, 1 slave no failover

<?php
$databases['default']['default'] = array (
  'driver' => 'autoslave',
  'master' => 'master', // Optional, defaults to 'master'
  'slave' => 'autoslave', // Optional, defaults to 'autoslave'
// Always use "master" for tables "semaphore" and "sessions"
  'tables' => array('sessions', 'semaphore'), // Optional, defaults to array('sessions', 'semaphore')
);

$databases['default']['master'] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'master.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
);

$databases['default']['autoslave'] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'slave.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
);

// Use locking that supports force master
$conf['lock_class'] = 'Drupal\autoslave\Lock\AutoSlaveLockBackend';
$conf['autoslave_lock_class'] = 'Drupal\Core\Lock\DatabaseLockBackend';

// Workaround for Drush (Drush doesn't support non-pdo database drivers).
// Workaround for update.php (similar problem as Drush).
if (drupal_is_cli() || basename($_SERVER['PHP_SELF']) == 'update.php') {
  $databases['default']['default'] = $databases['default']['master'];
}

?>

=== Extreme ===
1 master pool with 2 dbs, 2 slave pools with each 2 dbs, cross failover

<?php
$databases['default']['default'] = array (
  'driver' => 'autoslave',
  'master' => array('master', 'slave1', 'slave2'),
  'slave' => array('slave1', 'slave2', 'master'),
  'replication lag' => 2, // (defaults to $conf['autoslave_assumed_replication_lag'])
  'global replication lag' => TRUE, // Make replication lag mitigation work cross requests for all users. Defaults to TRUE.
  'invalidation path' => 'sites/default/files', // Path to store invalidation file for flagging unavailable connections. Defaults to empty.
  'watchdog on shutdown' => TRUE, // Enable watchdog logging during shutdown handlers. Defaults to FALSE. Enable only if using non-db watchdog logging.
  'init_commands' => array('autoslave' => "SET SESSION tx_isolation ='READ-COMMITTED'") // For MySQL InnoDB, make sure isolation level doesn't interfere with our intentions. Defaults to empty.
);

$databases['default']['master'][] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'master1.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
);

$databases['default']['master'][] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'master2.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
);

$databases['default']['slave1'][] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'slave1.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
  'readonly' => TRUE, // Defaults to FALSE, required for failover from master to slave to work
  'weight' => 70 // Defaults to 100
);

$databases['default']['slave1'][] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'slave2.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
  'readonly' => TRUE,
  'weight' => 30
);

$databases['default']['slave2'][] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'slave3.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
  'readonly' => TRUE,
);

$databases['default']['slave2'][] = array (
  'database' => 'mydb',
  'username' => 'username',
  'password' => 'password',
  'host' => 'slave4.example.com',
  'port' => '',
  'driver' => 'mysql',
  'prefix' => '',
  'readonly' => TRUE,
);

// Use locking that supports force master
$conf['lock_class'] = 'Drupal\autoslave\Lock\AutoSlaveLockBackend';
$conf['autoslave_lock_class'] = 'Drupal\Core\Lock\DatabaseLockBackend';

// Workaround for Drush (Drush doesn't support non-pdo database drivers).
// Workaround for update.php (similar problem as Drush).
if (drupal_is_cli() || basename($_SERVER['PHP_SELF']) == 'update.php') {
  $databases['default']['default'] = $databases['default']['master'];
}

?>

In order for failover to work for master to a slave (readonly), the AutoSlave needs to go into read-only mode. You may need to apply this bug-fix to Drupal Core, for it to work properly:

http://drupal.org/node/1889328 - Not all objects respect the query option "throw_exception"



