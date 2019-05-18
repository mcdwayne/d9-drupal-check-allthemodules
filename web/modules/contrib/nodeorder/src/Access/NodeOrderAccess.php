<?php

namespace Drupal\nodeorder\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\nodeorder\NodeOrderManagerInterface;
use Drupal\Core\Database\Connection;

/**
 * {@inheritdoc}
 */
class NodeOrderAccess implements ContainerInjectionInterface {

  /**
   * The current primary database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The nodeorder manager.
   *
   * @var \Drupal\nodeorder\NodeOrderManagerInterface
   */
  protected $nodeOrderManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, NodeOrderManagerInterface $node_order_manager) {
    $this->database = $database;
    $this->nodeOrderManager = $node_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('nodeorder.manager')
    );
  }

  /**
   * Field weight exists in taxonomy_index table.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function weightExists() {
    return $this->database->schema()->fieldExists('taxonomy_index', 'weight')
      ? AccessResult::allowed()
      : AccessResult::forbidden();
  }

  /**
   * Vocabulary orderable.
   *
   * @param \Drupal\taxonomy\Entity\Term $taxonomy_term
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function isVocabularyOrderable(Term $taxonomy_term) {
    $vocabulary_id = $taxonomy_term->getVocabularyId();

    return $this->nodeOrderManager->vocabularyIsOrderable($vocabulary_id)
      ? AccessResult::allowed()
      : AccessResult::forbidden();
  }

  /**
   * Administer order.
   *
   * @param \Drupal\taxonomy\Entity\Term $taxonomy_term
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function adminOrder(Term $taxonomy_term) {
    return $this->weightExists()->isAllowed() && $this->isVocabularyOrderable($taxonomy_term)->isAllowed()
      ? AccessResult::allowed()
      : AccessResult::forbidden();
  }

}
