<?php

namespace Drupal\monster_menus\Plugin\views\argument;

use Drupal\Core\Database\Connection;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler for an MM Tree entity and all of its children.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("mm_tree_children")
 */
class MMTreeChildren extends ArgumentPluginBase {

  /** @var ViewsHandlerManager $joinManager */
  protected $joinManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an MMTreeChildren object.
   *
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
      $container->get('database')
    );
  }

  /**
   * @inheritdoc
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $sub_query = $this->database->select('mm_tree_parents', 'p')
      ->fields('p', array('mmtid'))
      ->condition('parent', $this->argument)
      ->union(
        $this->database->select('mm_tree', 't')
          ->fields('t', array('mmtid'))
          ->condition('mmtid', $this->argument)
      );
    $definition = [
      'field' => 'mmtid',
      'table formula' => $sub_query,
      'left_table' => 'mm_tree',
      'left_field' => 'mmtid',
      'type' => 'INNER',
    ];
    $join = $this->joinManager->createInstance('standard', $definition);
    $this->alias = $this->query->addRelationship('mm_tree_children_subquery', $join, NULL, $this->relationship);
  }

}
