<?php

// This is what actually performs the migration logic.
// https://www.axelerant.com/resources/team-blog/migrations-writing-id-map-plugins
namespace Drupal\migrate_gathercontent\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;
use Drupal\migrate_gathercontent\DrupalGatherContentClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Source plugin for GatherContent items.
 *
 * @MigrateSource(
 *   id = "gathercontent_item",
 * )
 */
class Item extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\migrate_gathercontent\DrupalGatherContentClient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    // TODO: Implement initializeIterator() method.
    return $this->yieldContent();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, DrupalGatherContentClient $gathercontent_client) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->client = $gathercontent_client;
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
      $container->get('migrate_gathercontent.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function yieldContent() {
    $source_config = $this->migration->getSourceConfiguration();

    // Note: itemsGet This does not grab all the item data.
    $content = $this->client->itemsGetTemplate($source_config['project_id'], $source_config['template']);
    foreach ($content as $id => $item) {
      if ($item->templateId == $source_config['template']) {
        // Fetch full item and normalize its fields.
        $item_full = $this->client->itemGetFormatted($item->id);
        yield $this->toArray($item_full);
      }
    }
  }

  /**
   * Converts an item to an array.
   */
  public function toArray($item) {
    $data = $item;
    if (!empty($item['fields'])) {
      foreach ($item['fields'] as $field_id => $field) {
        $data[$field_id] = $field['value'];
      }
      unset($data['fields']);
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $definition = $this->getPluginDefinition();

    // Get fields from the template.
    $template = $this->client->templateGet($definition['template_id']);

    $fields = [];
    // Get field config.
    foreach ($template->config as $tab) {
      foreach($tab->elements as $fid => $field) {
        $fields[$fid] = $field->label;
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    $template = $this->client->templateGet($this->configuration['template']);
    return $template->usage->itemCount;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'id',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    return parent::prepareRow($row);
  }

}
