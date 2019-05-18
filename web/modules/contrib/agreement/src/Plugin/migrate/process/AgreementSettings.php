<?php

namespace Drupal\agreement\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Agreement settings process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "agreement_settings"
 * )
 */
class AgreementSettings extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $processPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   This migration.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   Migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager
   *   Process plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->migration = $migration;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->processPluginManager = $process_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Change property name for email recipient.
    $value['recipient'] = $value['email_recipient'];
    unset($value['email_recipient']);

    // Deal with roles which may not have been upgraded.
    if (!is_array($value['role'])) {
      $value['roles'] = [
        $this->getRoleId($value['role'], $migrate_executable),
      ];
    }
    else {
      $value['roles'] = [];
      foreach ($value['role'] as $role) {
        $value['roles'] = $this->getRoleId($role, $migrate_executable);
      }
    }
    unset($value['role']);

    // Map visibility settings and pages.
    $value['visibility'] = [
      'settings' => (int) $value['visibility_settings'],
      'pages' => [],
    ];
    $pages = preg_split('/\r?\n/', $value['visibility_pages']);
    if (!empty($pages)) {
      foreach ($pages as $page) {
        if ($page) {
          $value['visibility']['pages'][] = '/' . $page;
        }
      }
    }
    unset($value['visibility_pages']);
    unset($value['visibility_settings']);

    // Set a reset date.
    $value['reset_date'] = 0;

    // Prefix destination path.
    $value['destination'] = !empty($value['destination']) ? '/' . $value['destination'] : '';

    return $value;
  }

  /**
   * Gets the new role ID from the old role name.
   *
   * @param string $value
   *   The role name.
   * @param \Drupal\migrate\MigrateExecutableInterface $executable
   *   The migration execution.
   *
   * @return string
   *   The new role ID.
   *
   * @see \Drupal\migrate\Plugin\migrate\process\MachineName::transform()
   * @see \Drupal\user\Plugin\migrate\process\UserUpdate8002::transform()
   */
  protected function getRoleId($value, MigrateExecutableInterface $executable) {
    if ($value === 1) {
      return 'anonymous';
    }
    elseif ($value === 2) {
      return 'authenticated';
    }

    try {
      $row = new Row(['rid' => $value], ['rid' => ['type' => 'integer']]);
      $migration = $this->migrationPluginManager->createInstance('d7_user_role');
      $configuration = ['source' => 'rid'];

      $source_rid = $this->processPluginManager
        ->createInstance('get', $configuration, $this->migration)
        ->transform(NULL, $executable, $row, 'rid');

      if (!is_array($source_rid)) {
        $source_rid = [$source_rid];
      }
      $source_id_values['d7_user_role'] = $source_rid;

      // Break out of the loop as soon as a destination ID is found.
      if ($destination_ids = $migration->getIdMap()->lookupDestinationId($source_id_values['d7_user_role'])) {
        return reset($destination_ids);
      }
      return $value;
    }
    catch (PluginException $e) {
      return $value;
    }
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
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

}
