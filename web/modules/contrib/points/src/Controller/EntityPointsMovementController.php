<?php

namespace Drupal\points\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Views;

/**
 * Class EntityPointsMovementController.
 */
class EntityPointsMovementController extends ControllerBase {

  /**
   * Provides entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Provides renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs a EntityPointsMovementController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Provides an interface for entity type managers.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Turns a render array into a HTML string.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, Renderer $renderer) {
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function page() {
    // The current_path should look like /entity_type/entity_id/points.
    $current_path = \Drupal::service('path.current')->getPath();
    $path = explode("/", $current_path);

    $entity = $this->entityTypeManager->getStorage($path[1])->load($path[2]);

    $config_entities = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadMultiple();
    foreach ($config_entities as $config_entity) {
      $field_name = $config_entity->get('field_name');
      if ($config_entity->get('type') === 'entity_reference' && $config_entity->get('settings')['target_type'] === 'point' && $config_entity->get('entity_type') == $path[1] && substr($field_name, 6) == $path[3]) {
        $target_id = $entity->{$field_name}->target_id;
      }
    }

    /** @var \Drupal\views\ViewExecutable $view */
    $view = Views::getview('point_movement');
    $view_render_array = $view->buildRenderable('embed_1', [$target_id]);

    $build = [
      '#type' => 'markup',
      '#markup' => $this->renderer->render($view_render_array)
    ];
    return $build;
  }

}
