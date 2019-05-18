<?php

namespace Drupal\gridstack_field\Controller;


use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page callbacks for gridstack module.
 */
class GridstackFieldNodeController extends ControllerBase {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config_factory;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection) {
    $this->config_factory = $config_factory;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database')
    );
  }

  /**
   * Callback for loading nodes.
   *
   * @param        $node
   * @param string $display
   *
   * @return int
   */
  public function nodeCallback(NodeInterface $node, $display = 'teaser') {
    if (!$node->isPublished()) {
      $config = $this->configFactory->get('system.performance');
      $fast_404_html = strtr($config->get('fast_404.html'), ['@path' => Html::escape(\Drupal::request()->getUri())]);
      return new Response($fast_404_html, Response::HTTP_NOT_FOUND);
    }
    $node_for_displaying = node_view($node, $display);

    return new Response(render($node_for_displaying));
  }

  /**
   * Callback for autocomplete field.
   *
   * @param $field_name
   * @param $string
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function autocompleteCallback($field_name, $string) {
    // Using check functions on output to prevent cross site scripting attacks.
    $field_name = Html::escape($field_name);
    $string = Html::escape($string);

    $field = FieldStorageConfig::loadByName('node', $field_name);

    // Get array of content types from field settings.
    $type = array_filter($field['settings'], function ($v) {
      return $v === 1;
    });
    $type = array_keys($type);

    $connection = $this->connection;
    if (!empty($type)) {
      $matches = array();
      $res = $connection->select('node', 'n');
      $res->fields('n', array('title', 'nid', 'type'));
      $res->condition('title', '%' . $connection->escapeLike($string) . '%', 'LIKE');
      $res->condition('type', $type, 'IN');
      $res->range(0, 10);
      $query = $res->execute()->fetchAll();
      foreach ($query as $row) {
        $matches[$row->nid] = $row->title . '  [' . $row->type . ']';
      }
      // Return the result to the form in json.
      return new JsonResponse($matches);
    }
  }
}
