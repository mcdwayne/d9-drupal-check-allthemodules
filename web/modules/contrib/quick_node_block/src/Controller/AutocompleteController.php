<?php

namespace Drupal\quick_node_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {


  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct the Node-Title.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to check nid and title.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Retrieves suggestions for block category autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    $typed = $request->query->get('q');
    $matches = [];

    $query = $this->connection->select('node_field_data', 'n');
    $query->addField('n', 'nid');
    $query->addField('n', 'title');

    $db_or = $query->orConditionGroup();
    $db_or->condition('n.title', '%' . $typed . '%', 'LIKE');
    $db_or->condition('n.nid', $typed, '=');
    $query->condition($db_or);

    $results = $query->execute()->fetchAllKeyed(0, 1);
    foreach ($results as $nid => $title) {
      $matches[] = ['value' => $title . ' (' . $nid . ')', 'label' => $title];
    }
    return new JsonResponse($matches);
  }

}
