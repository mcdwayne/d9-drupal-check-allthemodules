<?php

/**
 * @file
 * Enable the Head2Head and Beta2Beta modules during tests.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$extensions = unserialize(db_query("SELECT data FROM {config} WHERE name = 'core.extension' AND collection = ''")->fetchField());
$extensions['module']['beta2beta'] = 0;
$extensions['module']['head2head'] = 0;
db_query("UPDATE {config} SET data = :data WHERE name = 'core.extension' AND collection = ''", [':data' => serialize($extensions)]);
