<?php

namespace Drupal\dat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Database connection entity.
 *
 * @ConfigEntityType(
 *   id = "dat_connection",
 *   label = @Translation("Database connection"),
 *   handlers = {
 *     "access" = "Drupal\dat\DatabaseConnectionAccessControlHandler",
 *     "list_builder" = "Drupal\dat\DatabaseConnectionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\dat\Form\DatabaseConnectionForm",
 *       "edit" = "Drupal\dat\Form\DatabaseConnectionForm",
 *       "delete" = "Drupal\dat\Form\DatabaseConnectionDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dat\DatabaseConnectionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "dat_connection",
 *   admin_permission = "administer dat connections",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "adminer" = "/admin/dat/connection/adminer/{dat_connection}",
 *     "editor" = "/admin/dat/connection/editor/{dat_connection}",
 *     "frame" = "/admin/dat/connection/frame/{dat_connection}/{dt_type}",
 *     "clone" = "/admin/dat/connection/{dat_connection}/clone",
 *     "add-form" = "/admin/dat/connection/add",
 *     "edit-form" = "/admin/dat/connection/{dat_connection}/edit",
 *     "delete-form" = "/admin/dat/connection/{dat_connection}/delete",
 *     "collection" = "/admin/dat/connections"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "driver",
 *     "server_name",
 *     "name",
 *     "host",
 *     "port",
 *     "username",
 *     "password",
 *     "allowed_schemas",
 *     "style",
 *   }
 * )
 */
class DatabaseConnection extends ConfigEntityBase implements DatabaseConnectionInterface {

  /**
   * The Database connection entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Database connection entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Database connection type (internal/external).
   *
   * @var string
   */
  protected $type;

  /**
   * The Server name (displayed in breadcrumbs).
   *
   * @var string
   */
  protected $server_name;

  /**
   * The Database name.
   *
   * @var string
   */
  protected $name;

  /**
   * The Database connection type (internal/external).
   *
   * @var string
   */
  protected $driver;

  /**
   * The Database connection host (localhost/IP).
   *
   * @var string
   */
  protected $host;

  /**
   * The Database connection port.
   *
   * @var int
   */
  protected $port;

  /**
   * The Database connection username.
   *
   * @var string
   */
  protected $username;

  /**
   * The Database connection password.
   *
   * @var string
   */
  protected $password;


  /**
   * The Database connection entity allowed Schemаs.
   *
   * @var array
   */
  protected $allowed_schemas;

  /**
   * The Database connection entity style.
   *
   * @var string
   */
  protected $style;

  /**
   * Helper function to get available list options for a field.
   *
   * @param string $field
   *   The field ID.
   *
   * @return array
   *   The array of options.
   */
  public static function getOptions(string $field) : array {
    $options = [];
    switch ($field) {
      case 'type':
        $options = [
          'internar' => t('Internal'),
          'external' => t('External'),
        ];
        break;

      case 'driver':
        $options = [
          'server' => t('MySQL'),
          'mssql' => t('MS SQL'),
          'mongo' => t('MongoDB'),
          'sqlite' => t('SQLite 3'),
          'sqlite2' => t('SQLite 2'),
          'pgsql' => t('PostgreSQL'),
          'simpledb' => t('SimpleDB'),
          'elastic' => t('Elasticsearch'),
        ];
        break;

    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostWithPort() : string {
    if (!empty($this->port)) {
      return $this->host . ':' . $this->port;
    }
    else {
      return $this->host;
    }
  }

}
