<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection.
 */

namespace Drupal\tracdelight\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\tracdelight\Tracdelight;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "tracdelight:product",
 *   label = @Translation("Tracdelight selection"),
 *   entity_types = {"product"},
 *   group = "tracdelight",
 *   weight = 1
 * )
 */
class TracdelightSelection extends DefaultSelection {

  /** @var \Drupal\tracdelight\Tracdelight */
  protected $tracdelightService;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Tracdelight $tracdelight) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    $this->tracdelightService = $tracdelight;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('tracdelight')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery(NULL, $match_operator);

    if (isset($match)) {
      if ($ein = $this->tracdelightService->getEinFromUri($match)) {
        $query->condition('ein', $ein, $match_operator);
      }
      else {
        if ($this->tracdelightService->stringSeemsToBeEin($match)) {
          $query->condition('ein', $match, $match_operator);
        }
        else {
          if (strpos($match, 'http:') !== 0) {
            $target_type = $this->configuration['target_type'];
            $entity_type = $this->entityManager->getDefinition($target_type);

            $label_key = $entity_type->getKey('label');
            $query->condition($label_key, $match, $match_operator);
          }
        }
      }
    }

    return $query;
  }


  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $entities = parent::getReferenceableEntities($match, $match_operator, $limit);

    if (empty($entities)) {
      $entities['product'] = array();

      if ($ein = $this->tracdelightService->getEinFromUri($match)) {
        $query = array('EIN' => $ein);
      } elseif ($this->tracdelightService->stringSeemsToBeEin($match)) {
        $query = array('EIN' => $match);
      }
      elseif (isset($match)) {
        $query = array('Query' => $match);
      }

      $products = $this->tracdelightService->queryProducts($query);
      $products = $this->tracdelightService->importProducts($products);

      foreach ($products as $product) {
          $entities['product'][$product->id()] = $product->title->value;
      }
    }

    return $entities;
  }
}
