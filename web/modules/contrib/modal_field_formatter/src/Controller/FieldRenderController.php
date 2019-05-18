<?php

namespace Drupal\modal_field_formatter\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FieldRenderController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FieldRenderController.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param string $entity_type
   * @param integer $entity
   * @param string $field_name
   */
  public function renderField(RouteMatchInterface $route_match, $entity_type = NULL, $entity_id = NULL, $field_name = NULL, $view_mode = NULL) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    // Full gets translated to default.
    $view_mode = $view_mode == 'full' ? 'default' : $view_mode;
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_display */
    $entity_display = $this->entityTypeManager->getStorage('entity_view_display')->load($entity_type . '.' . $entity->bundle() . '.' . $view_mode);
    $component = $entity_display->getComponent($field_name);

    $settings = $component['third_party_settings']['modal_field_formatter'];

    // We want to render the field as specified, except for the
    // modal link modification.
    unset($component['third_party_settings']['modal_field_formatter']);
    $field_render_array = $entity->{$field_name}->view($component);
    $options = [
      'dialogClass' => $settings['advanced']['modal_class'],
      'width' => $settings['advanced']['modal_width'],
    ];

    // Set title if set.
    $title = isset($settings['advanced']['modal_title']) ? $settings['advanced']['modal_title'] : '';

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $field_render_array, $options));
    return $response;
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param string $entity_type
   * @param integer $entity
   * @param string $field_name
   */
  public function hasFieldAccess(RouteMatchInterface $route_match, $entity_type = NULL, $entity_id = NULL, $field_name = NULL, $view_mode = NULL) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
    return AccessResult::allowedIf($entity->access('view'));
  }

}
