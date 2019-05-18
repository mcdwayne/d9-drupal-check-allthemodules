<?php

namespace Drupal\flexiform\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field_ui\Routing\RouteSubscriber as FieldUIRouteSubscriber;
use Drupal\flexiform\FormComponentTypePluginManager;
use Drupal\flexiform\FormComponent\FormComponentTypeCreateableInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for flexiform ui routes.
 */
class RouteSubscriber extends FieldUIRouteSubscriber {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form component type plugin manager.
   *
   * @var \Drupal\flexiform\FormComponentTypePluginManager
   */
  protected $formComponentTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\flexiform\FormComponentTypePluginManager $form_component_type_manager
   *   The form component type manager.
   */
  public function __construct(EntityManagerInterface $manager, EntityTypeManagerInterface $entity_type_manager, FormComponentTypePluginManager $form_component_type_manager) {
    parent::__construct($manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->formComponentTypeManager = $form_component_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Exposed form routes.
    foreach ($this->entityTypeManager->getStorage('entity_form_mode')->loadMultiple() as $form_mode_id => $form_mode) {
      if ($exposure_settings = $form_mode->getThirdPartySetting('flexiform', 'exposure')) {
        $options = [];
        foreach ($exposure_settings['parameters'] as $name => $parameter_info) {
          $options['parameters'][$name] = [
            'type' => 'entity:' . $parameter_info['entity_type'],
          ];
        }
        $options['parameters']['form_mode'] = [
          'type' => 'entity:entity_form_mode',
        ];

        // @todo: Access
        $route = new Route(
          $exposure_settings['path'],
          [
            '_controller' => '\Drupal\flexiform\Controller\FlexiformController::formModePage',
            '_title_callback' => '\Drupal\flexiform\Controller\FlexiformController::formModePageTitle',
            'form_mode' => $form_mode_id,
          ],
          [
            '_flexiform_form_mode_page_access_check' => 'TRUE',
          ],
          $options
        );
        $collection->add("flexiform.form_mode_page.{$form_mode_id}", $route);
      }
    }

    // Admin UI Routes.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route_name = $entity_type->get('field_ui_base_route')) {
        // Try to get the route from the current collection.
        if (!$entity_route = $collection->get($route_name)) {
          continue;
        }
        $path = $entity_route->getPath();

        $options = $entity_route->getOptions();
        if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
          $options['parameters'][$bundle_entity_type] = [
            'type' => 'entity:' . $bundle_entity_type,
          ];
        }
        // Special parameter used to easily recognize all Field UI routes.
        $options['_field_ui'] = TRUE;
        $options['_flexiform_form_entity'] = TRUE;

        $defaults = [
          'entity_type_id' => $entity_type_id,
        ];
        // If the entity type has no bundles and it doesn't use {bundle} in its
        // admin path, use the entity type.
        if (strpos($path, '{bundle}') === FALSE) {
          $defaults['bundle'] = !$entity_type->hasKey('bundle') ? $entity_type_id : '';
        }

        $route = new Route(
          "$path/form-display/{form_mode_name}/add-form-entity",
          [
            '_form' => '\Drupal\flexiform\Form\FormEntityAddForm',
            '_title' => 'Add form entity',
          ] + $defaults,
          ['_field_ui_form_mode_access' => 'administer ' . $entity_type_id . ' form display'],
          $options
        );
        $collection->add("entity.entity_form_display.{$entity_type_id}.form_mode.form_entity_add", $route);

        $route = new Route(
          "$path/form-display/{form_mode_name}/edit-form-entity/{entity_namespace}",
          [
            '_form' => '\Drupal\flexiform\Form\FormEntityEditForm',
            '_title' => 'Configure form entity',
          ] + $defaults,
          ['_field_ui_form_mode_access' => 'administer ' . $entity_type_id . ' form display'],
          $options
        );
        $collection->add("entity.entity_form_display.{$entity_type_id}.form_mode.form_entity_edit", $route);

        // Handle component types that allow repeatable components.
        foreach ($this->formComponentTypeManager->getDefinitions() as $plugin_id => $definition) {
          $component_type = $this->formComponentTypeManager->createInstance($plugin_id);
          if ($component_type instanceof FormComponentTypeCreateableInterface) {
            $route = new Route(
              "$path/form-display/add-component-" . str_replace('_', '-', $plugin_id),
              [
                'component_type' => $plugin_id,
                'form_mode_name' => 'default',
                '_form' => '\Drupal\flexiform\Form\FormComponentAddForm',
                '_title' => 'Add ' . $definition['label'],
              ] + $defaults,
              [
                '_permission' => 'administer ' . $entity_type_id . ' display',
              ],
              $options
            );
            $collection->add("entity.entity_form_display.{$entity_type_id}.default.component_type.{$plugin_id}.add", $route);

            $route = new Route(
              "$path/form-display/{form_mode_name}/add-component-" . str_replace('_', '-', $plugin_id),
              [
                'component_type' => $plugin_id,
                '_form' => '\Drupal\flexiform\Form\FormComponentAddForm',
                '_title' => 'Add ' . $definition['label'],
              ] + $defaults,
              [
                '_permission' => 'administer ' . $entity_type_id . ' display',
              ],
              $options
            );
            $collection->add("entity.entity_form_display.{$entity_type_id}.form_mode.component_type.{$plugin_id}.add", $route);
          }
        }
      }
    }
  }

}
