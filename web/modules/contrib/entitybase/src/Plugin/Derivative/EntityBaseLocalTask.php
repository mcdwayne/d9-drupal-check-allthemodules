<?php

namespace Drupal\entity_base\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entities.
 */
class EntityBaseLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an EntityBaseLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();

    $entity_base_types = entity_base_types();

    foreach($entity_base_types as $entity_base_type_id => $entity_base_type) {

      $entity_base_definition = $entity_base_type->get('additional')['entity_base'];

      $has_collection_path = $entity_base_type->hasLinkTemplate('collection');
      $has_canonical_path = $entity_base_type->hasLinkTemplate('canonical');
      $has_edit_path = $entity_base_type->hasLinkTemplate('edit-form');
      $has_delete_path = $entity_base_type->hasLinkTemplate('delete-form');

      // Entity View tab.
      if ($has_canonical_path) {
        $this->derivatives["entity.$entity_base_type_id.canonical"] = array(
          'route_name' => "entity.$entity_base_type_id.canonical",
          'title' => $this->t('View'),
          'base_route' => "entity.$entity_base_type_id.canonical",
          'weight' => 0,
        );
      }

      // Entity Edit tab.
      if ($has_edit_path) {
        $this->derivatives["entity.$entity_base_type_id.edit_form"] = array(
          'route_name' => "entity.$entity_base_type_id.edit_form",
          'title' => $this->t('Edit'),
          'base_route' => "entity.$entity_base_type_id.canonical",
          'weight' => 10,
        );
      }

      // Entity Delete tab.
      if ($has_delete_path) {
        $this->derivatives["entity.$entity_base_type_id.delete_form"] = array(
          'route_name' => "entity.$entity_base_type_id.delete_form",
          'title' => $this->t('Delete'),
          'base_route' => "entity.$entity_base_type_id.canonical",
          'weight' => 200,
        );
      }

      // Admin List tab.
      if ($has_collection_path) {
        $this->derivatives["entity.$entity_base_type_id.admin_list"] = array(
          'route_name' => "entity.$entity_base_type_id.collection",
          'title' => $entity_base_definition['names']['label_plural'],
          'base_route' => "entity.$entity_base_type_id.collection",
          'weight' => 0,
        );
      }


    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
