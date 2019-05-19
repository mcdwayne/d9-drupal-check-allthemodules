<?php

namespace Drupal\smallads\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 7 smallad migrations based on smallad types.
 */
class D7SmalladDeriver extends DeriverBase implements ContainerDeriverInterface {
  use MigrationDeriverTrait;

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache;

  /**
   * The CCK plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   *
   * @var type
   */
  protected $fieldPluginManager;

  /**
   * Whether or not to include translations.
   *
   * @var bool
   */
  protected $includeTranslations;

  /**
   * D7SmalladDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface $cck_manager
   *   The CCK plugin manager.
   */
  public function __construct($base_plugin_id, MigrateCckFieldPluginManagerInterface $cck_manager, MigrateFieldPluginManagerInterface $field_manager, $translations) {
    $this->basePluginId = $base_plugin_id;
    $this->cckPluginManager = $cck_manager;
    $this->fieldPluginManager = $field_manager;
    $this->includeTranslations = $translations;
    $this->derivatives = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.migrate.cckfield'),
      $container->get('plugin.manager.migrate.field'),
      $container->get('module_handler')->moduleExists('content_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source_plugin = static::getSourcePlugin('d7_field_instance');
    $source_plugin->checkRequirements();

    foreach (['offer'  => 'Offer', 'want' => 'Want'] as $smallad_type_id => $label) {
      // Read all field instance definitions in the source database.
      foreach ($source_plugin as $row) {
        $values = $base_plugin_definition;
        $values['label'] = t('@label (@type)', [
          '@label' => $label,
          '@type' => $smallad_type_id,
        ]);
        $values['source']['smallad_type'] = $smallad_type_id;
        $values['destination']['default_bundle'] = $smallad_type_id;
        $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($values);

        if ($row->getSourceProperty('entity_type') == 'node') {
          if ($row->getSourceProperty('bundle') == 'proposition') {
            //each row describes a fieldAPI on the entity
            $info = $row->getSource();
            $field_name = $info['field_name'];
            try {
              $plugin_id = $this->fieldPluginManager->getPluginIdFromFieldType($info['type'], ['core' => 7], $migration);
              $this->fieldPluginManager
                ->createInstance($plugin_id, ['core' => 7], $migration)
                ->processFieldValues($migration, $field_name, $info);
            }
            catch (PluginNotFoundException $ex) {
              try {
                $plugin_id = $this->cckPluginManager->getPluginIdFromFieldType($info['type'], ['core' => 7], $migration);
                $this->cckPluginManager
                  ->createInstance($plugin_id, ['core' => 7], $migration)
                  ->processCckFieldValues($migration, $field_name, $info);
              }
              catch (PluginNotFoundException $ex) {
                $migration->setProcessOfProperty($field_name, $field_name);
              }
            }
          }
        }
        $this->derivatives[$smallad_type_id] = $migration->getPluginDefinition();
      }
    }
    return $this->derivatives;
  }

}
