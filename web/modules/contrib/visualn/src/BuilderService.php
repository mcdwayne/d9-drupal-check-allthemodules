<?php

namespace Drupal\visualn;

use Drupal\visualn\Manager\BuilderManager;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\ResourceInterface;

//@todo: add visualn.drawing_builder (or visualn.builder) service (as renderer analogy)
//  and move services into service dependencies

/**
 * Class BuilderService.
 */
class BuilderService implements BuilderServiceInterface {

  /**
   * Drupal\visualn\Manager\BuilderManager definition.
   *
   * @var \Drupal\visualn\Manager\BuilderManager
   */
  protected $visualNBuilderManager;

  /**
   * Drupal\visualn\Manager\DrawerManager definition.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Drupal\Core\Entity\EntityStorageInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * Constructs a new BuilderService object.
   */
  public function __construct(BuilderManager $plugin_manager_visualn_builder, DrawerManager $plugin_manager_visualn_drawer, EntityTypeManager $entity_type_manager) {
    $this->visualNBuilderManager = $plugin_manager_visualn_builder;
    $this->visualNDrawerManager = $plugin_manager_visualn_drawer;
    $this->visualNStyleStorage = $entity_type_manager->getStorage('visualn_style');
  }

  /**
   * Standard entry point to create drawings based on resource and configuration data.
   *
   * @todo: add docblock
   * @todo: add to the service interface
   * @todo: review arguments order:
   *   base_drawer_id and window_parameters
   */
  public function makeBuildByResource(ResourceInterface $resource, $visualn_style_id, array $drawer_config, array $drawer_fields, $base_drawer_id = '', $window_parameters = []) {

    $build = [];

    // @todo: move builder plugin id discovery for the drawer into DefaultManager::prepareBuild()?

    if (!empty($visualn_style_id)) {
      // load style and get builder requested by drawer from drawer plugin definition
      $visualn_style = $this->visualNStyleStorage->load($visualn_style_id);
      $drawer_plugin_id = $visualn_style->getDrawerPlugin()->getPluginId();
      $builder_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['builder'];
    }
    elseif (!empty($base_drawer_id)) {
      $drawer_plugin_id = $base_drawer_id;
      $builder_plugin_id = $this->visualNDrawerManager->getDefinition($drawer_plugin_id)['builder'];
    }
    else {
      return $build;
    }


    // generate vuid for the drawing
    $vuid = \Drupal::service('uuid')->generate();

    // generate html selector for the drawing (where to attach drawing selector)
    $html_selector = 'visualn-drawing--' . substr($vuid, 0, 8);

    // get builder configuration, load builder plugin and prepare drawing build
    $builder_config = [
      'visualn_style_id' => $visualn_style_id,
      'drawer_config' => $drawer_config,
      'drawer_fields' => $drawer_fields,
      'html_selector' => $html_selector,
      // @todo: this was introduced later, for drawer preview page
      'base_drawer_id' => $base_drawer_id,
    ];

    $builder_plugin = $this->visualNBuilderManager->createInstance($builder_plugin_id, $builder_config);
    $builder_plugin->setWindowParameters($window_parameters);
    $builder_plugin->prepareBuild($build, $vuid, $resource);

    // use a template instead of attaching html_selector as prefix when build is ready
    // also this allows to override theming of all drawing wrappers
    // @todo: or maybe even attach it inside builder::prepareBuild() method
    $build = [
      '#theme' => 'visualn_drawing_build_wrapper',
      '#build' => $build,
      '#html_selector' => $html_selector,
    ];

    return $build;

  }

}
