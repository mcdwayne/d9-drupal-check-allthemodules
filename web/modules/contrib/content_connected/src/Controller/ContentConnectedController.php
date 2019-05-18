<?php

namespace Drupal\content_connected\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_connected\ContentConnectedManagerInterface;

/**
 * Class ContentConnectedController.
 *
 * @package Drupal\content_connected\Controller
 */
class ContentConnectedController extends ControllerBase {

  /**
   * The contentconnected manager.
   *
   * @var \Drupal\content_connected\ContentConnectedManagerInterface
   */
  protected $contentConnectedmanager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ContentConnectedManagerInterface $content_connected_manager) {
    $this->contentConnectedmanager = $content_connected_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
     $container->get('content_connected.manager')
    );
  }

  /**
   * Contentconnectedcontroller.
   *
   * @return string
   *   Return table of matching.
   */
  public function contentConnectedoverview($node) {
    return [
      '#type' => 'markup',
      '#markup' => $this->contentConnectedmanager->renderMatches($node),
    ];
  }

  /**
   * The _title_callback for the entity.node.content_connected route.
   *
   * @param \Drupal\node\NodeTypeInterface $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function addPageTitle($node) {
    $node_storage = $this->entityManager()->getStorage('node')->load($node);
    return $this->t('Content connected with @name', array('@name' => $node_storage->label()));
  }

}
