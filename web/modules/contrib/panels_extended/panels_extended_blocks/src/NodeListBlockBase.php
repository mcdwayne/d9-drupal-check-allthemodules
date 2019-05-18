<?php

namespace Drupal\panels_extended_blocks;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\panels_extended\JsonBlockBase;
use Drupal\panels_extended_blocks\BlockConfig\AlterBlockDataInterface;
use Drupal\panels_extended_blocks\BlockConfig\AlterQueryInterface;
use Drupal\panels_extended_blocks\BlockConfig\AlterQueryRangeInterface;
use Drupal\panels_extended_blocks\BlockConfig\AlterQueryResultInterface;
use Drupal\panels_extended_blocks\BlockConfig\NodeListBaseConfig;
use Drupal\panels_extended_blocks\BlockConfig\NodeTypeFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base implementation for a block which displays a list of nodes.
 */
abstract class NodeListBlockBase extends JsonBlockBase implements ContainerFactoryPluginInterface {

  /**
   * Set to TRUE in subclass to dump the SQL on screen.
   */
  const SQL_DEBUG = FALSE;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Local storage for the fetched data.
   *
   * @var array
   */
  protected $data;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->entityTypeManager = $entityTypeManager;

    $this->configs = array_merge($this->getBlockConfigsToAdd(), [new NodeListBaseConfig()]);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Maximum number of items to show by default.
   *
   * @return int
   *   The max number of items to show by default.
   */
  abstract public function getNumberOfItems();

  /**
   * Get a list of block configs to add.
   *
   * @return \Drupal\panels_extended\BlockConfig\BlockConfigBase[]
   *   A list of block configs to add.
   */
  protected function getBlockConfigsToAdd() {
    return [];
  }

  /**
   * Gets a select statement for a table.
   *
   * @param string $tableName
   *   Table name.
   * @param string $tableAlias
   *   Table alias.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The select statement.
   */
  public function getSelectForTable($tableName, $tableAlias) {
    return $this->connection->select($tableName, $tableAlias);
  }

  /**
   * Gets a list of node IDs to be used in this block.
   *
   * @return int[]
   *   The list of node IDs for this block.
   */
  protected function getNodeIds() {
    $query = $this->getSelectForTable('node_field_data', 'nfd');
    $query->fields('nfd', ['nid']);

    $start = 0;
    $length = $this->getNumberOfItems();
    foreach ($this->configs as $config) {
      if ($config instanceof AlterQueryInterface) {
        $config->alterQuery($query, FALSE);
      }
      if ($config instanceof AlterQueryRangeInterface) {
        $config->alterQueryRangeDelta($start, $length);
      }
    }
    if ($length <= 0) {
      $result = [];
    }
    else {
      $query->range($start, $length);

      if (static::SQL_DEBUG) {
        var_dump($this->label());
        var_dump((string) $query);
        var_dump($query->getArguments());
      }

      $result = $query->execute()->fetchCol();
      $result = array_map(function ($nid) {
        return (int) $nid;
      }, $result);
    }

    foreach ($this->configs as $config) {
      if ($config instanceof AlterQueryResultInterface) {
        $config->alterQueryResult($result);
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    if (isset($this->data)) {
      return $this->data;
    }
    $nids = $this->getNodeIds();

    foreach ($this->configs as $config) {
      if ($config instanceof AlterBlockDataInterface) {
        $config->alterBlockData($nids);
      }
    }

    $this->data = $nids;
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderIfNoData() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDataForHtmlRendering(array $data) {
    $nids = array_filter($data, function ($value) {
      return is_numeric($value);
    });

    if (empty($nids)) {
      return [];
    }

    $nodes = $this->getEntityTypeManager()->getStorage('node')->loadMultiple($nids);
    if (empty($nodes)) {
      return [];
    }

    $items = [];
    /** @var \Drupal\node\NodeInterface $node */
    foreach ($nodes as $node) {
      $items[] = [
        '#type' => 'link',
        '#url' => $node->toUrl(),
        '#title' => $node->id() . ' - ' . $node->getTitle(),
      ];
    }
    return [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
  }

  /**
   * Gets the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Gets a list of node types to be used in this block.
   *
   * @return array
   *   The node types. If empty, all available types should be used.
   */
  public function getNodeTypes() {
    foreach ($this->configs as $config) {
      if ($config instanceof NodeTypeFilter) {
        return $config->getSelectedNodeTypes();
      }
    }
    return [];
  }

}
