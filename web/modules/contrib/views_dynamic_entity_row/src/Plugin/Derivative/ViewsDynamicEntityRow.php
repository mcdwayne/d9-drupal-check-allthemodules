<?php

namespace Drupal\views_dynamic_entity_row\Plugin\Derivative;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\Derivative\ViewsEntityRow;
use Drupal\views\ViewsData;
use Drupal\views_dynamic_entity_row\DynamicEntityRowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic views row plugin definitions.
 */
class ViewsDynamicEntityRow extends ViewsEntityRow {
  use StringTranslationTrait;

  /**
   * The Views Dynamic Entity Row settings manager.
   *
   * @var \Drupal\views_dynamic_entity_row\DynamicEntityRowManagerInterface
   */
  protected $dynamicRowManager;

  /**
   * Constructs a ViewsDynamicEntityRow object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\views\ViewsData $views_data
   *   The views data service.
   */
  public function __construct($base_plugin_id, EntityManagerInterface $entity_manager, ViewsData $views_data, DynamicEntityRowManagerInterface $dynamic_row_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityManager = $entity_manager;
    $this->viewsData = $views_data;
    $this->dynamicRowManager = $dynamic_row_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager'),
      $container->get('views.views_data'),
      $container->get('views_dynamic_entity_row.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Add support for entity types which have a views and VDER integration.
      if (
        ($base_table = $entity_type->getBaseTable()) &&
        $this->viewsData->get($base_table) &&
        $this->entityManager->hasHandler($entity_type_id, 'view_builder') &&
        $this->dynamicRowManager->isSupported($entity_type_id)
      ) {
        $this->derivatives[$entity_type_id] = [
          'id' => 'entity:' . $entity_type_id,
          'provider' => 'views_dynamic_entity_row',
          'title' => $this->t('@label (dynamic)', [
            '@label' => $entity_type->getLabel(),
          ]),
          'help' => $this->t('Display the @label', [
            '@label' => $entity_type->getLabel()
          ]),
          'base' => [$entity_type->getDataTable() ?: $entity_type->getBaseTable()],
          'entity_type' => $entity_type_id,
          'display_types' => ['normal'],
          'class' => $base_plugin_definition['class'],
        ];
      }
    }

    return $this->derivatives;
  }

}
