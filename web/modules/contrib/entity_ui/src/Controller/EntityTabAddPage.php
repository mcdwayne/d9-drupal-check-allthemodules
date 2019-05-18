<?php

namespace Drupal\entity_ui\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\entity_ui\Plugin\EntityTabContentManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the page to add a new entity tab entity.
 */
class EntityTabAddPage implements ContainerInjectionInterface {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Entity Tab content plugin manager
   *
   * @var \Drupal\entity_ui\Plugin\EntityTabContentManager
   */
  protected $entityTabContentPluginManager;

  /**
   * Constructs a new EntityTabForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\entity_ui\Plugin\EntityTabContentManager
   *   The entity tab plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTabContentManager $entity_tab_content_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTabContentPluginManager = $entity_tab_content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_ui_tab_content')
    );
  }

  /**
   * Builds the content for entity tab add page.
   *
   * @return
   *  A render array.
   */
  public function content($target_entity_type_id) {
    $build = [];

    $labels = [];

    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);
    $content_tab_plugin_definitions = $this->entityTabContentPluginManager->getDefinitions();
    foreach ($content_tab_plugin_definitions as $plugin_id => $definition) {

      if (!$definition['class']::appliesToEntityType($target_entity_type, $definition)) {
        continue;
      }

      $labels[$plugin_id] = $definition['label'];
    }

    natcasesort($labels);

    $items = [];
    foreach ($labels as $plugin_id => $plugin_label) {
      $items[$plugin_id] = [
        'label' => $plugin_label,
        'description' => $content_tab_plugin_definitions[$plugin_id]['description'],
        'add_link' => Link::createFromRoute($plugin_label, 'entity.entity_tab.add_form', [
          'target_entity_type_id' => $target_entity_type_id,
          'plugin_id' => $plugin_id,
        ]),
      ];
    }

    $build['plugin_type'] = [
      // Use entity_add_list.html.twig, even though these are not bundles,
      // because it's the same UI pattern of a list of links to create things
      // with descriptions.
      '#theme' => 'entity_add_list',
      '#bundles' => $items,
      '#add_bundle_message' => t("No content plugins apply to this entity type"),
    ];

    return $build;
  }

  /**
   * Returns the title for the entity tab add page.
   *
   * @return string
   *  The title.
   */
  public function title($target_entity_type_id) {
    $target_entity_type = $this->entityTypeManager->getDefinition($target_entity_type_id);

    return t("Add entity tab for @target-type-label", [
      '@target-type-label' => $target_entity_type->getLabel(),
    ]);
  }

}
