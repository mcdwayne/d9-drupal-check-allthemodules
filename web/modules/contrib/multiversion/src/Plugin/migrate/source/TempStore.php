<?php

namespace Drupal\multiversion\Plugin\migrate\source;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * User source from json file.
 *
 * @MigrateSource(
 *   id = "tempstore"
 * )
 */
class TempStore extends SourcePluginBase {

  /**
   * @var KeyValueStoreExpirableInterface
   */
  protected $tempStore;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity.manager'),
      $container->get('keyvalue.expirable')
    );
  }

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param MigrationInterface $migration
   *   The migration.
   * @param EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param KeyValueExpirableFactoryInterface $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityManagerInterface $entity_manager, KeyValueExpirableFactoryInterface $temp_store_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $entity_manager);
    $this->tempStore = $temp_store_factory->get('multiversion_migration_' . $this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $values = $this->tempStore->getAll();
    $result = new \ArrayIterator($values);
    // Suppress errors (for PHP 5).
    @$result->uksort([$this, 'sortKeys']);
    return $result;
  }

  /**
   * Sorts values by default language, translations in default language will be
   * always first. This will make sure that translations in non-default
   * languages will be saved after the translation in default language.
   *
   * @param string $a
   * @param string $b
   *
   * @return int
   */
  public static function sortKeys(string $a, string $b) {
    $default_language_id = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $a_contains_default_language_id = strpos($a, '.' . $default_language_id);
    $b_contains_default_language_id = strpos($b, '.' . $default_language_id);

    if ($a_contains_default_language_id !== FALSE && $b_contains_default_language_id === FALSE) {
      return -1;
    }
    elseif ($a_contains_default_language_id === FALSE && $b_contains_default_language_id !== FALSE) {
      return 1;
    }
    else {
      return 0;
    }
  }

}
