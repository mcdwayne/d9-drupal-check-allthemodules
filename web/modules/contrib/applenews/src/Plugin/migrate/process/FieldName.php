<?php

namespace Drupal\applenews\Plugin\migrate\process;

use Drupal\applenews\ApplenewsManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an applenews_field_name plugin.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: applenews_field_name
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "applenews_field_name"
 * )
 */
class FieldName extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The applenews manager service.
   *
   * @var \Drupal\applenews\ApplenewsManager
   */
  protected $applenewsManager;

  /**
   * Constructs a FieldName plugin.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\applenews\ApplenewsManager $applenews_manager
   *   The applenews manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ApplenewsManager $applenews_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->applenewsManager = $applenews_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('applenews.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $fields = $this->applenewsManager->getFields($row->getSourceProperty('entity_type'));
    $field_name = '';
    foreach ($fields as $field_id => $field_instances) {
      if (in_array($row->getSourceProperty('type'), $field_instances['bundles'])) {
        $value = $field_id;
        break;
      }
    }
    if (empty($value)) {
      throw new MigrateSkipRowException(sprintf('Entity %s:%s:%s does not have a matching applenews field.', $row->getSourceProperty('entity_type'), $row->getSourceProperty('type'), $row->getSourceProperty('entity_id')));
    }

    return $value;
  }

}
