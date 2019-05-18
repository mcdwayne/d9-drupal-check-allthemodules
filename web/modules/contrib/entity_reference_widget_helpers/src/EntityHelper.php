<?php

namespace Drupal\entity_reference_widget_helpers;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DepenencyInjection\ContainerInterface;
use Drupal\Component\Utility\Unicode;

/**
 *
 */
class EntityHelper {

  /**
   * Inheritdoc.
   */
  public function __construct(QueryFactory $entity_query, EntityTypeManagerInterface $entity_manager) {
    $this->entity_query = $entity_query;
    $this->entity_manager = $entity_manager;
  }

  /**
   * Inheritdoc.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Generate a list of id => title options for a select list .
   *
   * @param string $type
   * @param array $bundles
   *
   * @return array
   */
  public function getOptions($type, $bundles) {
    $query = $this->entity_query->get($type);
    if ($bundles) {
      $query->condition('type', $bundles, 'IN');
    }
    $ids = $query->execute();

    $entities = $this->entity_manager->getStorage($type)->loadMultiple($ids);
    $entity_summary = [];
    foreach ($entities as $id => $entity) {
      $entity_summary[$id] = [
        'id' => $id,
        'bundle' => $entity->bundle(),
        'label' => Unicode::truncate($entity->label(), 80),
      ];
    }

    // This works as long as there's only one entity type per reference field.
    $bundle_type_id = $entity->getEntityType()->getBundleEntityType();
    $entity_type_storage = \Drupal::entityTypeManager()->getStorage($bundle_type_id);

    // Unique bundles.
    $bundles = array_unique(array_map(function ($e) {
      return $e['bundle'];
    }, $entity_summary));

    $opts = [];
    foreach ($bundles as $bundle) {
      $bundle_label = $entity_type_storage
        ->load($bundle)
        ->label();
      $opts[$bundle_label] = [];
      foreach ($entity_summary as $id => $summary) {
        if ($summary['bundle'] == $bundle) {
          $opts[$bundle_label][$id] = $summary['label'];
        }
      }
      asort($opts[$bundle_label]);
    }
    return $opts;
  }

  /**
   * Formatted paragraph bundle name + description.
   */
  public function getParagraphDescription($bundle) {
    $paragraph_type = $this->entity_manager->getStorage('paragraphs_type')->load($bundle);
    $output = $paragraph_type->get('label');
    if ($description = $paragraph_type->get('description')) {
      $output .= ' - ' . $description;
    }
    return $output;
  }

  /**
   * Count number of entities.
   */
  public function countEntities($type, $bundles) {
    $query = $this->entity_query->get($type)
      ->count();
    if ($bundles) {
      $query->condition('type', $bundles, 'IN');
    }
    return $query->execute();
  }

}
