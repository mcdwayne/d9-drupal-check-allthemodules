<?php

namespace Drupal\dat\Plugin\Dat\Adminer;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\dat\DatAdminerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a connection autologin for the Database Admin Tool.
 *
 * @DatAdminerPlugin(
 *   id = "connection",
 *   name = @Translation("Connection Plugin"),
 *   description = @Translation("Connection Plugin"),
 *   weight = 1,
 *   group = "system",
 *   types = {
 *     "adminer",
 *     "editor"
 *   }
 * )
 */
class ConnectionPlugin extends DatAdminerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity Database Connection.
   *
   * @var \Drupal\dat\Entity\DatabaseConnectionInterface
   */
  protected $connection;

  /**
   * Constructs a ParagraphsBehaviorBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $connection = $route_match->getParameter('dat_connection');
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * Get server name displayed in breadcrumbs.
   *
   * @param string $server
   *   The server name.
   *
   * @return string
   *   HTML code or null
   */
  public function serverName($server) {
    return $this->connection->get('server_name');
  }

  /**
   * Get key used for permanent login.
   *
   * @param bool $create
   *
   * @return string
   *   Cryptic string which gets combined with password or false in case of
   *   an error.
   */
  public function permanentLogin($create = FALSE) {
    // Key used for permanent login.
    return 'bd07e4f9fc68839e862e7e5f865e06ed';
  }

  /**
   * Identifier of selected database.
   *
   * @return string
   *   The DB Identifier.
   */
  public function database() {
    return $this->connection->get('name');
  }

  /**
   * Get cached list of databases.
   *
   * @param bool $flush
   *
   * @return array
   *   The array of DB.
   */
  public function databases($flush = TRUE) {
    $return = [];
    foreach (get_databases($flush) as $db) {
      if ($db == $this->connection->get('name')) {
        $return[] = $db;
      }
    }
    return $return;
  }

  /**
   * Get list of schemas.
   *
   * @return array
   *   The list of schemas.
   */
  public function schemas() {
    $return = [];
    $allowed_schemas = $this->connection->get('allowed_schemas');
    if (!empty($allowed_schemas)) {
      $allowed_schemas = array_map('trim', explode("\n", $allowed_schemas));
      foreach (schemas() as $schema) {
        if (in_array($schema, $allowed_schemas)) {
          $return[] = $schema;
        }
      }
    }
    else {
      $return = schemas();
    }

    return $return;
  }

  /**
   * Authorize the user.
   *
   * @param string $login
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return mixed
   *   True for success, string for error message, false for unknown error.
   */
  public function login($login, $password) {
    return TRUE;
  }

  /**
   * Connection parameters.
   *
   * @return array
   *   ($server, $username, $password)
   */
  public function credentials() {
    return [
      $this->connection->getHostWithPort(),
      $this->connection->get('username'),
      $this->connection->get('password'),
    ];
  }

  /**
   * Include CSS.
   */
  public function head() {
    print '<link rel="stylesheet" type="text/css" href="' . file_create_url($this->connection->get('style')) . '"/>' . "\n";
  }

}
