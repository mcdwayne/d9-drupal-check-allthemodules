<?php

/**
 * @file
 * Definition of Drupal\media_view_addons\Plugin\views\field\MediaViewAddonsNodesField.
 */

namespace Drupal\media_view_addons\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media_view_addons\EntityRelationshipManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin to add a top level entity link to the media view.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("media_view_addons_nodes_field")
 */
class MediaViewAddonsNodesField extends FieldPluginBase {
  /**
   * @var \Drupal\media_view_addons\EntityRelationshipManagerInterface
   */
  protected $entityRelationshipManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('media_view_addons.relationship_manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * MediaViewAddonsNodesField constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\media_view_addons\EntityRelationshipManagerInterface $entity_relationship_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityRelationshipManagerInterface $entity_relationship_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityRelationshipManager = $entity_relationship_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::render().
   *
   * Renders the top level node edit links for each media View row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The values retrieved from a single row of a view's query result.
   *
   * @return \Drupal\Component\Render\MarkupInterface|\Drupal\Core\StringTranslation\TranslatableMarkup|\Drupal\views\Render\ViewsRenderPipelineMarkup|string
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Exception
   */
  public function render(ResultRow $row) {
    if (!empty($this->view->field['mid'])) {
      // Get the mid from the media View.
      $row_media_image_id = intval($this->view->field['mid']->getValue($row));

      // Get all the top level node IDs.
      if ($top_level_node_ids = $this->entityRelationshipManager->topLevelNids('media', $row_media_image_id)) {
        $links = [];
        $node_storage = $this->entityTypeManager->getStorage('node');
        foreach ($top_level_node_ids as $top_level_node_id) {
          $node = $node_storage->load($top_level_node_id);
          $links[$top_level_node_id] = [
            'title' => $this->t('@title', ['@title' => $node->title->value]),
            'url' => Url::fromRoute('entity.node.edit_form', ['node' => $top_level_node_id]),
          ];
        }
        // Allow other modules to alter the links array.
        $this->moduleHandler->invokeAll('media_view_addons_links', [&$links]);
        // Make node edit links look like fancy operation dropdowns.
        $operations['data'] = [
          '#theme' => 'links',
          '#links' => $links,
          '#attributes' => ['class' => ['admin-list']],
          '#cache' => [
            'tags' => ['node_list'],
          ],
        ];
        return $this->renderer->render($operations);
      }
      else {
        return t('No nodes retrieved');
      }
    }
  }
}
