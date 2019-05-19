<?php

/**
 * @file
 * Contains \Drupal\xwechat_material\Controller\MaterialListController.
 */

namespace Drupal\xwechat_material\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pyramid\Component\WeChat\WeChat;
use Pyramid\Component\WeChat\Request;
use Pyramid\Component\WeChat\Response;

class MaterialListController extends ControllerBase {
  
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The x wechat config.
   */
  protected $config;

  /**
   * Constructs a MateriaListController object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
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
   * Displays a list of materias.
   */
  public function listMaterials($xwechat_config = NULL) {  
    $nodes = Node::loadMultiple();
    
    $rows = array();
    foreach ($nodes as $nid => $node) {
      $rows[$nid] = array(
        'nid' => $node->id(),
        'title' => $node->getTitle(),
        'type' => $node->getType(),
        'stamp' => $node->getCreatedTime(),
      );
    }

    $header = array(
      'id' => t('ID'),
      'title' => t('Title'),
      'type' => t('Type'),     
      'stamp' => t('Timestamp'),
    );

    $table = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
    $markup = drupal_render($table);

    return array(
        '#type' => 'markup',
        '#markup' => $markup,
    );
  }
}
