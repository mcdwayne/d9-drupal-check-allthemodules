<?php

namespace Drupal\gathercontent\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Perform custom value transformation.
 *
 * @MigrateProcessPlugin(
 *   id = "gather_content_taxonomy"
 * )
 *
 * @code
 * taxonomy_field:
 *   plugin: gather_content_taxonomy
 *   bundle: vid
 *   source: field
 * @endcode
 */
class GatherContentTaxonomy extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $selected_options = [];

    $taxonomy = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'gathercontent_option_ids' => $value,
        'vid' => $this->configuration['bundle'],
      ]);

    if ($taxonomy) {
      $selected_options = array_keys($taxonomy);
    }

    return reset($selected_options);
  }

}
