<?php

/**
 * @file
 * Contains \Drupal\dfw\DfwAutocomplete.
 */

namespace Drupal\dfw;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;

/**
 * Defines a helper class to get user autocompletion results.
 */
class DfwAutocomplete {

  /**
   * The database connection to query for the user names.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory to get the anonymous user name.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a DfwAutocomplete object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to query for the user names.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(Connection $connection, ConfigFactory $config_factory) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
  }

  /**
   * Get matches for the autocompletion of user names.
   *
   * @param string $string
   *   The string to match for usernames.
   *
   * @param bool $include_anonymous
   *   (optional) TRUE if the the name used to indicate anonymous users (e.g.
   *   "Anonymous") should be autocompleted. Defaults to FALSE.
   *
   * @return array
   *   An array containing the matching usernames.
   */
  public function getMatches($string, $include_anonymous = FALSE) {
    $matches = array();

    $matches = get_class_methods(drupal_container());
    $ret_matches = array();
    foreach ($matches as $key => $value) {
      if (strpos($value, $string) !== FALSE) {
        $ret_matches[$value] = $value;
      }
    }
    // @todo fill this with class vars;
    //$matches = get_class_vars() (drupal_container());

    return $ret_matches;
  }

}
