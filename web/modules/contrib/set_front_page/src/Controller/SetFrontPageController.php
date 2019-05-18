<?php

namespace Drupal\set_front_page\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\node\NodeInterface;
use Drupal\set_front_page\SetFrontPageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define in which entity id displayed the set_front_page tab.
 */
class SetFrontPageController extends ControllerBase {

  /**
   * The set frontpage manager.
   *
   * @var \Drupal\set_front_page\SetFrontPageManager
   */
  protected $setFrontPageManager;

  /**
   * Constructs a new SetFrontPageLocalTasks.
   *
   * @param \Drupal\set_front_page\SetFrontPageManager $setFrontPageManager
   *   The set_front_page manager.
   */
  public function __construct(SetFrontPageManager $setFrontPageManager) {
    $this->setFrontPageManager = $setFrontPageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('set_front_page.manager')
    );
  }

  /**
   * Check if a term can be a frontpage.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   The term object.
   */
  public function limitTerms(TermInterface $taxonomy_term) {
    return $this->limitEntities($taxonomy_term);
  }

  /**
   * Check if a node can be a frontpage.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   */
  public function limitNodes(NodeInterface $node) {
    return $this->limitEntities($node);
  }

  /**
   * Check if this entity can be a frontpage.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   */
  public function limitEntities(EntityInterface $entity) {
    if ($this->setFrontPageManager->entityCanBeFrontPage($entity)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
