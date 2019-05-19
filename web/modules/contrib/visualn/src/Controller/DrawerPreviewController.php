<?php

namespace Drupal\visualn\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\Core\Form\FormBuilder;
use Drupal\visualn\Form\DrawerPreviewForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class DrawerPreviewController.
 */
class DrawerPreviewController extends ControllerBase {

  /**
   * Drupal\visualn\Manager\DrawerManager definition.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * Drupal\Core\Form\FormBuilder definition.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DrawerManager $plugin_manager_visualn_drawer, FormBuilder $form_builder) {
    $this->visualNDrawerManager = $plugin_manager_visualn_drawer;
    $this->formBuilder = $form_builder;
  }

  // @todo: add a similar interface for setup bakers

  // @todo: it would be great to have such a 'live' inteface with real resources
  //    e.g. while uploading a file into an entity or view
  //    And even more useful for setup bakers and real resources (actually all
  //    these are already UI issues)

  // @todo: also data generators may use setup bakers configs to define
  //   optiomal initial generator config


  /**
   * Page for drawer preview with configuration and data generator subforms.
   *
   * @return array
   *   Return Drawer preveiw form render array.
   */
  public function page($id) {

    $base_drawer_id = $id;
    $drawer_config = [];

    // if plugin does not exist, return Page not found response
    if (!$this->visualNDrawerManager->hasDefinition($base_drawer_id)) {
      throw new NotFoundHttpException();
    }
    // check drawer 'role' key, exclude wrappers
    elseif ($this->visualNDrawerManager->getDefinition($base_drawer_id)['role'] == 'wrapper') {
      throw new NotFoundHttpException();
    }

    $drawer_plugin = $this->visualNDrawerManager->createInstance($base_drawer_id, $drawer_config);

    // get drawer preview form, pass plugin_id as build_info parameter
    $form = $this->formBuilder->getForm(DrawerPreviewForm::class, $base_drawer_id);

    return $form;
  }

  public function title($id) {
    $definition = $this->visualNDrawerManager->getDefinition($id);

    return $this->t('@label <em>drawer preview</em>', ['@label' => $definition['label']]);;
  }

}
