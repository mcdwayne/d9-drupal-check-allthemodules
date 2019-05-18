<?php

namespace Drupal\entity_list;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Drupal\entity_list\Entity\EntityList;
use Drupal\entity_list\Plugin\EntityListDisplayManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityListViewBuilder.
 *
 * @package Drupal\entity_list
 */
class EntityListViewBuilder implements EntityHandlerInterface, EntityViewBuilderInterface {

  protected $entityType;

  protected $entityTypeManager;

  protected $languageManager;

  protected $themeRegistry;

  /**
   * EntityListViewBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity_type id.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Theme\Registry|null $theme_registry
   *   The theme registry.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, Registry $theme_registry = NULL) {
    $this->entityType = $entity_type;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->themeRegistry = $theme_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('theme.registry')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    /** @var \Drupal\entity_list\Entity\EntityList $entity */
    $build = $this->build($entity, $view_mode);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    // TODO: Implement viewMultiple() method.
    //    dpm($entities);
    return [];
  }

  /**
   * Build the entity list render array.
   *
   * @param \Drupal\entity_list\Entity\EntityList $entity_list
   *   The entity list configuration.
   * @param string $view_mode
   *   The current view mode used to render the list. (!= view mode used to
   *   render each items of the list)
   *
   * @return array
   *   A render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function build(EntityList $entity_list, $view_mode = 'full') {
    $query_plugin = $entity_list->getEntityListQueryPlugin();
    if (!empty($query_plugin)) {

      $storage = $this->entityTypeManager->getStorage($query_plugin->getEntityTypeId());

      $query_plugin->buildQuery();

      \Drupal::moduleHandler()->alter([
        'entity_list_query',
        'entity_list_query_' . $entity_list->id(),
      ], $query_plugin, $entity_list);

      $entity_ids = $query_plugin->execute();
      $entities = $storage->loadMultiple($entity_ids);

      // Call plugin render method.
      if ($plugin = $entity_list->getEntityListDisplayPlugin()) {
        return $plugin->render($entities, $view_mode);
      }

    }

    return [];
  }

  /**
   * {@inheritdoc}
   *
   * Copied from EntityViewBuilder.
   */
  public function resetCache(array $entities = NULL) {
    // If no set of specific entities is provided, invalidate the entity view
    // builder's cache tag. This will invalidate all entities rendered by this
    // view builder.
    // Otherwise, if a set of specific entities is provided, invalidate those
    // specific entities only, plus their list cache tags, because any lists in
    // which these entities are rendered, must be invalidated as well. However,
    // even in this case, we might invalidate more cache items than necessary.
    // When we have a way to invalidate only those cache items that have both
    // the individual entity's cache tag and the view builder's cache tag, we'll
    // be able to optimize this further.
    if (isset($entities)) {
      $tags = [];
      foreach ($entities as $entity) {
        $tags = Cache::mergeTags($tags, $entity->getCacheTags());
        $tags = Cache::mergeTags($tags, $entity->getEntityType()
          ->getListCacheTags());
      }
      Cache::invalidateTags($tags);
    }
    else {
      Cache::invalidateTags($this->getCacheTags());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = []) {
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display_options = []) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [$this->entityType->id() . '_view'];
  }

}
