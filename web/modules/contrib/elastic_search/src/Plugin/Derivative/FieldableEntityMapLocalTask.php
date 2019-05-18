<?php

namespace Drupal\elastic_search\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for all entity bundles.
 *
 * @see \Drupal\elastic_search\Routing\RouteSubscriber
 */
class FieldableEntityMapLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an DevelLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface      $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {

      //Because we only pass this elastic edit path to the types we want
      //we can also use it as a check wether to render the tab
      $hasEditPath = $entity_type->hasLinkTemplate('elastic-mapping-add');
      $hasAdminPath = $entity_type->hasLinkTemplate('elastic-mapping-admin');
      if ($hasEditPath) {
        //TODO - this needs to be done in a plugin
        if ($entity_type_id === 'taxonomy_vocabulary') {
          $this->derivatives["$entity_type_id.elastic_mapping_tab"] = [
            'route_name' => "entity.$entity_type_id.elastic_edit",
            'title'      => $this->t('Elastic Field Map'),
            'base_route' => "entity.$entity_type_id.overview_form",
            'weight'     => 100,
          ];
        } else {
          $this->derivatives["$entity_type_id.elastic_mapping_tab"] = [
            'route_name' => "entity.$entity_type_id.elastic_edit",
            'title'      => $this->t('Elastic Field Map'),
            'base_route' => "entity.$entity_type_id.edit_form",
            'weight'     => 100,
          ];
        }
      }
      if ($hasAdminPath) {
        $this->derivatives["$entity_type_id.elastic_admin_tab.edit"] = [
          'route_name' => "entity.$entity_type_id.edit_form",
          'title'      => $this->t('Edit'),
          'base_route' => 'elastic_search.server_form',
          'weight'     => 100,
        ];
      }
    }
    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }

}
