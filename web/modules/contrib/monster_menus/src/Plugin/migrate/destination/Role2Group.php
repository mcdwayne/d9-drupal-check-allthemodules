<?php

namespace Drupal\monster_menus\Plugin\migrate\destination;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * @MigrateDestination(
 *   id = "mm_role2group"
 * )
 */
class Role2Group extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The config object.
   *
   * @var ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * Constructs an entity destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->config_factory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = array()) {
    $import_rid = $row->getDestinationProperty('rid');
    if ($config = $this->config_factory->getEditable('user.role.' . $import_rid)) {
      $config
        ->set('mm_gid', $row->getSourceProperty('gid'))
        ->set('mm_exclude', $row->getDestinationProperty('negative'))
        ->save();
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'rid' => $this->t('Role ID'),
      'mm_gid' => $this->t('MM Tree ID of the group'),
      'mm_exclude' => $this->t('TRUE if the role should be the inverse of the group'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'rid' => [
        'type' => 'integer',
        'unsigned' => TRUE,
        'alias' => 'r',
      ],
    ];
  }

}
